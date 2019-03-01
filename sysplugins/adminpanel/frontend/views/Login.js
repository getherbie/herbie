import m from "mithril";
import Auth from "../components/Auth"
import ErrorHandler from "../components/ErrorHandler";

export default {
    view() {
        return [
            m(".pure-form", [
                m(".test", [
                    m("input.pure-input-1[type=text][placeholder=Username][required]", {
                        oninput: (e) => {
                            Auth.setUsername(e.target.value)
                        },
                        onkeyup: (e) => {
                            if (e.keyCode == 13) {
                                this.login()
                            }
                            e.redraw = false
                        },
                        value: Auth.username
                    })
                ]),
                m(".test", [
                    m("input.pure-input-1[type=password][placeholder=Password][required]", {
                        oninput: (e) => {
                            Auth.setPassword(e.target.value)
                        },
                        onkeyup: (e) => {
                            if (e.keyCode == 13) {
                                this.login()
                            }
                            e.redraw = false
                        },
                        value: Auth.password
                    })
                ]),
                m("button.pure-button[type=button]", {
                    onclick: () => this.login()
                }, "Login"),
            ]),
            m(".link", [
                m("a[href='/" + WEB_URL + "']", {}, "To website")
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
