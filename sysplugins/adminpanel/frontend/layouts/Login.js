import m from "mithril"

export default {
    view: function (vnode) {
        return m("#layout--login.app", [
            m(".layout__inner--login", vnode.children)
        ])
    }
}
