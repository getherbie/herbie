import m from "mithril";
import Auth from "../components/Auth"
import ErrorHandler from "../components/ErrorHandler";

export default {
    view() {
        return [
            m(".pure-form", [
                m(".test", [
                    m("input.pure-input-1-2[type=text][placeholder=Username][required]", {
                        oninput: (e) => {
                            Auth.setUsername(e.target.value)
                        },
                        value: Auth.username
                    })
                ]),
                m(".test", [
                    m("input.pure-input-1-2[type=password][placeholder=Password][required]", {
                        oninput: (e) => {
                            Auth.setPassword(e.target.value)
                        },
                        value: Auth.password
                    })
                ]),
                m("button.pure-button[type=button]", {onclick: () => this.login()}, "Login"),
            ]),
            m(".link", [
                m("a[href='/example/web']", {}, "To website")
            ])
        ]
    },
    login() {
        Auth.login()
            .catch((error) => {
                ErrorHandler.show(error)
            })
    }
}
