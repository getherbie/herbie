import m from "mithril";
import Auth from "../components/Auth"

export default {
    view: function () {
        return [
            m(".pure-form", [
                m(".test", [
                    m("input.pure-input-1-2[type=text][placeholder=Username][required]", {
                        oninput: function (e) {
                            Auth.setUsername(e.target.value)
                        },
                        value: Auth.username
                    })
                ]),
                m(".test", [
                    m("input.pure-input-1-2[type=password][placeholder=Password][required]", {
                        oninput: function (e) {
                            Auth.setPassword(e.target.value)
                        },
                        value: Auth.password
                    })
                ]),
                m("button.pure-button[type=button]", {onclick: Auth.login}, "Login"),
            ]),
            m(".link", [
                m("a[href='/example/web']", {}, "To website")
            ])
        ]
    }
}
