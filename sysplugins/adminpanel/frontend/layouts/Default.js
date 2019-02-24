import m from "mithril"

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
                            m("a.pure-menu-link[href='/page']", {oncreate: m.route.link}, "Page")
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/data']", {oncreate: m.route.link}, "Data")
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/media']", {oncreate: m.route.link}, "Media")
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/tools']", {oncreate: m.route.link}, "Tools")
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/test']", {oncreate: m.route.link}, "Test")
                        ]),
                        m("li.pure-menu-item", {}, "-"),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/example/web']", {}, "Frontend")
                        ]),
                        m("li.pure-menu-item", [
                            m("a.pure-menu-link[href='/logout']", {oncreate: m.route.link}, "Logout")
                        ]),
                    ])
                ])
            ]),
            m("div#main", [
                m("div.content", [
                    m("div.pure-g", [
                        m("div.pure-u-1", vnode.children)
                    ])
                ])
            ])
        ])
    }
}
