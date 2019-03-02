import m from "mithril";

// layout
import DefaultLayout from "./layouts/Default";
import LoginLayout from "./layouts/Login";

// views
import Page from "./views/Page";
import Data from "./views/Data";
import Login from "./views/Login";
import Media from "./views/Media";
import Tools from "./views/Tools";
import Index from "./views/Index";
import Test from "./views/Test";
import Error from "./views/Error";


// components
import Auth from "./components/Auth";
import t from "./components/Translate";

const PAGE_TITLE = t("Adminpanel");

m.route(document.body, "/", {
    "/": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Index") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__index a');
            return m(DefaultLayout, m(Index))
        }
    },
    "/page": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Page") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(DefaultLayout, m(Page))
        }
    },
    "/data": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Data") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(DefaultLayout, m(Data))
        }
    },
    "/media": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Media") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(DefaultLayout, m(Media))
        }
    },
    "/tools": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Tools") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(DefaultLayout, m(Tools))
        }
    },
    "/test": {
        onmatch: onMatchHandler,
        render() {
            document.title = t("Test") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(DefaultLayout, m(Test, {}))
        }
    },
    "/login": {
        render() {
            document.title = t("Login") + " / " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__login a');
            return m(LoginLayout, m(Login, {}))
        }
    },
    "/logout": {
        onmatch: () => {
            Auth.logout();
            m.route.set("/login")
        }
    },
    "/:404...": {
        render() {
            document.title = t("Error") + " / " + PAGE_TITLE;
            setActiveMenuItem();
            return m(LoginLayout, m(Error))
        }
    }
});

function __(key) {
    return key;
}

function onMatchHandler() {
    if (!localStorage.getItem("auth-token")) m.route.set("/login")
}

function setActiveMenuItem(selector = '') {
    let els = document.querySelectorAll('.site-navigation a');
    els.forEach((el) => {
        el.classList.remove('active');
    });

    if (selector === '') {
        return;
    }

    let el = document.querySelector(selector);
    if (el) {
        el.classList.add('active');
    }
}
