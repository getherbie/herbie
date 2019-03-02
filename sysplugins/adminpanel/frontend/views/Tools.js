import m from "mithril";
import t from "../components/Translate"

export default {
    oninit() {
    },
    view() {
        return [
            m("h1", t("Tools")),
            m("h3", t("Folders")),
            m("p", t("The following folders can be emptied.")),
            m("table.pure-table.pure-table-horizontal", [
                m("tbody", [
                    m("tr", [
                        m("td", t("Data Cache")),
                        m("td", [
                            m("span.count", "1"),
                            " ",
                            t("file(s)")
                        ]),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                alert(t('Empty data cache?'))
                            }
                        }, t("Empty"))),
                    ]),
                    m("tr", [
                        m("td", t("Page cache")),
                        m("td", [
                            m("span.count", "1"),
                            " ",
                            t("files(s)")
                        ]),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                alert(t('Empty page cache?'))
                            }
                        }, t("Empty"))),
                    ]),
                    m("tr", [
                        m("td", t("Web assets")),
                        m("td", [
                            m("span.count", "1"),
                            " ",
                            t("file(s)")
                        ]),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                alert(t('empty web assets?'))
                            }
                        }, t("Empty"))),
                    ]),
                ])
            ]),
            m("h3", t("Configuration")),
            m("p", t("The following configuration files can be formatted.")),
            m("table.pure-table.pure-table-horizontal", [
                m("tbody", [
                    m("tr", [
                        m("td", t("Configuration")),
                        m("td", [
                            m("span.count", "1"),
                            " ",
                            t("file(s)")
                        ]),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                alert(t('Format configuration?'))
                            }
                        }, t("Format"))),
                    ])
                ])
            ]),
        ]
    }
}
