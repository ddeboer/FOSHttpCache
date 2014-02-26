backend default {
    .host = "localhost";
    .port = "8080";
}

acl invalidators {
    "localhost";
}

sub vcl_recv {
    if (req.restarts == 0 && req.http.cookie) {
        set req.request = "HEAD";
        return (pass);
    }

    if (req.restarts > 0 && req.request == "HEAD") {
        set req.request = "GET";
        unset req.http.cookie;
    }

    if (req.request == "PURGE") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed";
        }
        return (lookup);
    }

    if (req.request == "BAN") {
        if (!client.ip ~ invalidators) {
            error 405 "Not allowed.";
        }

        if (req.http.x-cache-tags) {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
                + " && obj.http.x-cache-tags ~ " + req.http.x-cache-tags
            );
        } else {
            ban("obj.http.x-host ~ " + req.http.x-host
                + " && obj.http.x-url ~ " + req.http.x-url
                + " && obj.http.content-type ~ " + req.http.x-content-type
            );
        }

        error 200 "Banned";
    }

    if (req.http.Cache-Control ~ "no-cache" && client.ip ~ invalidators) {
        set req.hash_always_miss = true;
    }
}

sub vcl_fetch {

    # Set ban-lurker friendly custom headers
    set beresp.http.x-url = req.url;
    set beresp.http.x-host = req.http.host;
}

sub vcl_hit {
    if (req.request == "PURGE") {
        purge;
        error 200 "Purged";
    }
}

sub vcl_miss {
    if (req.request == "PURGE") {
        purge;
        error 404 "Not in cache";
    }
}

sub vcl_deliver {
    # Add extra headers if debugging is enabled
    if (resp.http.x-cache-debug) {
        if (obj.hits > 0) {
            set resp.http.X-Cache = "HIT";
        } else {
            set resp.http.X-Cache = "MISS";
        }
    } else {
        # Remove ban-lurker friendly custom headers when delivering to client
        unset resp.http.x-url;
        unset resp.http.x-host;
    }

    if (req.request == "HEAD" && resp.http.x-hash) {
        set req.http.x-hash = resp.http.x-hash;
        return (restart);
    }
}
