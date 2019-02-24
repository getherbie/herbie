import m from "mithril"

export default {
    view: function (vnode) {
        return m("#layout.app", [
            m("a#menuLink.menu-link[href='#']", [
                m('span')
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
