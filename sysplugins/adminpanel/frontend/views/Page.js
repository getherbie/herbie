import m from "mithril";

export default {
    oninit() {
    },
    view() {
        let html = '<h1>Page</h1>';
        return m.trust(html)
    }
}
