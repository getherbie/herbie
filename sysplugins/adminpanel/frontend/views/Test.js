import m from "mithril";
import t from "../components/Translate"
import Test from "../models/Test";
import ErrorHandler from "../components/ErrorHandler";

export default {
    name: "",
    error: "",
    oninit() {
        Test.loadList()
            .catch((error) => {
                ErrorHandler.show(error)
            })
    },
    view() {
        return [
            m("h1", t("Test")),
            m("div.pure-form", [
                m("input.pure-input[data-target=test.name]", {
                    oninput: (e) => {
                        this.name = e.target.value
                        e.redraw = false
                    },
                    onkeypress: (e) => {
                        this.error = ""
                        e.redraw = false
                    },
                    onkeyup: (e) => {
                        if (e.keyCode == 13) {
                            this.name = e.target.value
                            this.add()
                        }
                        e.redraw = false
                    },
                    value: this.name,
                }),
                m("button.pure-button", {
                    onclick: (e) => {
                        this.add()
                    }
                }, t("Add")),
            ]),
            this.renderError(),
            m("br"),
            this.renderTable()
        ];
    },
    renderError() {
        if (this.error == "") {
            return m("span.error.hidden", this.error)
        }
        return m("span.error", this.error)
    },
    renderTable() {
        if (Test.list.length === 0) {
            return m("div", t("No entries"));
        }
        return m("table#test-table.pure-table.pure-table-horizontal", [
            m("thead", [
                m("tr", [
                    m("th[width=99%]", t("Type")),
                    m("th[width=1%]"),
                ])
            ]),
            m("tbody#test-tbody", Test.list.map((test, index) => {
                return m("tr", [
                    m("td", test.name),
                    m("td", [
                        m("button.pure-button.button-small", {
                            onclick: (e) => {
                                this.remove(index)
                            }
                        }, t("Delete"))
                    ])
                ])
            }))
        ])
    },
    add() {
        if (this.name == "") {
            return;
        }
        Test.add({name: this.name})
            .then(() => {
                this.name = "";
            })
            .catch((error) => {
                ErrorHandler.show(error)
            });
            /*.catch((error) => {
                this.error = error.response.errors.name[0]
            });*/
    },
    remove(index) {
        if (confirm(t("Delete entry?"))) {
            Test.remove(index)
                .catch((error) => {
                    ErrorHandler.show(error)
                })
        }
    }
}
