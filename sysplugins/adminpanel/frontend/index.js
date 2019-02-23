import m from "mithril";
import Layout from "./views/Layout";
import Page from "./views/Page";
import Data from "./views/Data";
import Media from "./views/Media";
import Tools from "./views/Tools";
import Index from "./views/Index";
import Test from "./views/Test";

const PAGE_TITLE = "Adminpanel";

m.route(document.body, "/", {
    "/": {
        render() {
            document.title = "Index // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__index a');
            return m(Layout, m(Index))
        }
    },
    "/page": {
        render() {
            document.title = "Page // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(Layout, m(Page))
        }
    },
    "/data": {
        render() {
            document.title = "Data // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(Layout, m(Data))
        }
    },
    "/media": {
        render() {
            document.title = "Media // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(Layout, m(Media))
        }
    },
    "/tools": {
        render() {
            document.title = "Tools // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(Layout, m(Tools))
        }
    },
    "/test": {
        render() {
            document.title = "Test // " + PAGE_TITLE;
            setActiveMenuItem('.site-navigation__test a');
            return m(Layout, m(Test, {}))
        }
    },
    "/:404...": {
        render() {
            document.title = "Fehler // " + PAGE_TITLE;
            setActiveMenuItem();
            return m(Layout, m(Error))
        }
    }
});

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
