import m from "mithril";

export default {
    oninit: function () {
    },
    view: function () {
        let html = '<h1>Page</h1>';
        return m.trust(html)
    }
}
