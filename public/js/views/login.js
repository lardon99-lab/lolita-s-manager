        if (document.cookie.includes('PHPSESSID') && window.location.pathname.includes('login.php')) {
            window.history.pushState(null, null, window.location.href);
            window.onpopstate = function () {
                window.history.go(1);
            };
        }

        window.onload = function () {
            if (typeof history.pushState === "function") {
                history.pushState("jibberish", null, null);
                window.onpopstate = function () {
                    history.pushState('jibberish', null, null);
                };
            }
        }

        if (window.performance && window.performance.navigation.type === 2) {
            location.reload();
        }
    
