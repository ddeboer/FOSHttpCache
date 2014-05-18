sub vcl_recv {
    if (req.restarts > 0 && req.http.X-FOSHttpCache-OriginalMethod) {
        set req.method = req.http.X-FOSHttpCache-OriginalMethod;
        set req.url     = req.http.X-FOSHttpCache-OriginalUrl;

        unset req.http.X-FOSHttpCache-OriginalUrl;
        unset req.http.X-FOSHttpCache-OriginalMethod;
    }

    if (req.restarts == 0 && req.http.cookie && (req.method == "GET" || req.method == "HEAD")) {
        set req.http.X-FOSHttpCache-TempCookie     = req.http.cookie;
        set req.http.X-FOSHttpCache-OriginalUrl    = req.url;
        set req.http.X-FOSHttpCache-OriginalMethod = req.method;
        set req.http.X-FOSHttpCache-SessionId      = req.http.cookie;

        set req.url     = "/user_context_head.php";
        set req.method = "HEAD";

        unset req.http.cookie;
    }
}

sub vcl_miss {
    // When creating backend request, varnish force GET method (bug ?)
#    set bereq.method = req.method;

#    if (bereq.http.X-FOSHttpCache-TempCookie) {
#        set bereq.http.cookie = bereq.http.X-FOSHttpCache-TempCookie;
#    }
}

sub vcl_deliver {
    set resp.http.X-HeadCache = "MISS";

    if (req.method == "HEAD" && resp.http.X-FOSHttpCache-Hash) {
        set req.http.X-FOSHttpCache-Hash = resp.http.X-FOSHttpCache-Hash;

        if (obj.hits > 0) {
            set req.http.X-HeadCache = "HIT";
        } else {
            set req.http.X-HeadCache = "MISS";
        }

        return (restart);
    } elsif (req.http.X-HeadCache) {
        set resp.http.X-HeadCache = req.http.X-HeadCache;
    }
}
