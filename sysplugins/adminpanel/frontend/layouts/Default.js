import m from "mithril"
import t from "../components/Translate"

export default {
    view: function (vnode) {
        return m("#layout.app", [
            m("a#menuLink.menu-link[href='#']", [
                m('span')
            ]),
            m("div#menu", [
                m("div.pure-menu", [
                    m("a.pure-menu-heading[href='/']", {oncreate: m.route.link}, "HERBIE"),
                    m("ul.pure-menu-list", [
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/page']", {oncreate: m.route.link}, t("Page"))
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/data']", {oncreate: m.route.link}, t("Data"))
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/media']", {oncreate: m.route.link}, t("Media"))
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/tools']", {oncreate: m.route.link}, t("Tools"))
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/test']", {oncreate: m.route.link}, t("Test"))
                        ]),
                        m("li.pure-menu-item", {}, "-"),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='" + WEB_URL + "']", {}, t("Frontend"))
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/logout']", {oncreate: m.route.link}, t("Logout"))
                        ]),
                    ])
                ])
            ]),
            m("div#main", [
                m("div#flashError.alert.alert-error.hidden", "Error"),
                m("div#flashInfo.alert.alert-info.hidden", "Info"),
                m("div#flashSuccess.alert.alert-success.hidden", "Success"),
                m("div.content", [
                    m("div.pure-g", [
                        m("div.pure-u-1", vnode.children)
                    ])
                ])
            ])
        ])
    }
}
