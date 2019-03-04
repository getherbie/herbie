import m from "mithril";
import t from "../components/Translate"
import Auth from "../components/Auth"
import ErrorHandler from "../components/ErrorHandler";

export default {
    view() {
        return [
            m(".pure-form", [
                m("div", [
                    m("input.pure-input-1", {
                        oninput: (e) => {
                            Auth.setUsername(e.target.value)
                        },
                        onkeyup: (e) => {
                            if (e.keyCode === 13) {
                                this.login()
                            }
                            e.redraw = false
                        },
                        autofocus: true,
                        placeholder: t("Username"),
                        required: true,
                        type: "text",
                        value: Auth.username
                    })
                ]),
                m("div", [
                    m("input.pure-input-1", {
                        oninput: (e) => {
                            Auth.setPassword(e.target.value)
                        },
                        onkeyup: (e) => {
                            if (e.keyCode === 13) {
                                this.login()
                            }
                            e.redraw = false
                        },
                        placeholder: t("Password"),
                        required: true,
                        type: "text",
                        value: Auth.password
                    })
                ]),
                m("button.pure-button", {
                    onclick: () => this.login()
                }, "Login"),
            ]),
            m("div", [
                m("a[href='" + WEB_URL + "']", {}, t("To website"))
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
