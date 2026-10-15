/*
 * Progressive enhancements. Every page works without this file.
 */
(function () {
    "use strict";

    var root = document.documentElement;
    root.classList.add("js");

    // Mobile navigation ------------------------------------------------

    var header = document.querySelector("[data-header]");
    var toggle = document.querySelector("[data-nav-toggle]");

    if (header && toggle) {
        var setOpen = function (open) {
            toggle.setAttribute("aria-expanded", String(open));
            if (open) {
                header.setAttribute("data-open", "");
            } else {
                header.removeAttribute("data-open");
            }
        };

        toggle.addEventListener("click", function () {
            setOpen(!header.hasAttribute("data-open"));
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && header.hasAttribute("data-open")) {
                setOpen(false);
                toggle.focus();
            }
        });

        document.addEventListener("click", function (event) {
            if (header.hasAttribute("data-open") && !header.contains(event.target)) {
                setOpen(false);
            }
        });
    }

    // Header shadow once the page scrolls -------------------------------

    if (header) {
        var onScroll = function () {
            header.classList.toggle("is-scrolled", window.scrollY > 8);
        };
        onScroll();
        window.addEventListener("scroll", onScroll, { passive: true });
    }

    // Toasts ------------------------------------------------------------

    var dismiss = function (toast) {
        if (!toast || toast.classList.contains("is-leaving")) {
            return;
        }
        toast.classList.add("is-leaving");
        window.setTimeout(function () {
            toast.remove();
        }, 300);
    };

    document.querySelectorAll("[data-toast]").forEach(function (toast) {
        var close = toast.querySelector("[data-toast-close]");
        if (close) {
            close.addEventListener("click", function () {
                dismiss(toast);
            });
        }

        // Errors stay until dismissed; everything else fades away.
        if (!toast.classList.contains("alert-error")) {
            window.setTimeout(function () {
                dismiss(toast);
            }, 6000);
        }
    });

    // Live countdowns ---------------------------------------------------

    var plural = function (n, word) {
        return n + " " + word + (n === 1 ? "" : "s");
    };

    var formatCountdown = function (startsAt) {
        var seconds = Math.round((startsAt - Date.now()) / 1000);

        if (seconds <= 0) {
            return "Happening now";
        }

        var minutes = Math.ceil(seconds / 60);
        if (minutes < 60) {
            return "Starts in " + plural(minutes, "minute");
        }

        var hours = Math.floor(minutes / 60);
        var rest = minutes % 60;
        if (hours < 24) {
            return "Starts in " + plural(hours, "hour") + (rest ? " " + rest + " min" : "");
        }

        return null; // leave the server-rendered date in place
    };

    var countdowns = Array.prototype.slice.call(document.querySelectorAll("[data-countdown]"));

    var tick = function () {
        countdowns.forEach(function (element) {
            var startsAt = Date.parse(element.getAttribute("data-countdown"));
            if (isNaN(startsAt)) {
                return;
            }
            var text = formatCountdown(startsAt);
            if (text !== null && element.textContent.trim() !== text) {
                element.textContent = text;
            }
        });
    };

    if (countdowns.length) {
        tick();
        window.setInterval(tick, 30000);
    }

    // Prevent double submissions ----------------------------------------

    document.querySelectorAll("form[data-submit-once]").forEach(function (form) {
        form.addEventListener("submit", function () {
            var button = form.querySelector('button[type="submit"]');
            if (!button) {
                return;
            }
            window.setTimeout(function () {
                button.disabled = true;
                button.setAttribute("aria-busy", "true");
            }, 0);
        });
    });
})();
