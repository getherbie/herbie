import m from "mithril";
import t from "../components/Translate"
import ErrorHandler from "../components/ErrorHandler";
import Tools from "../models/Tools";

export default {
    oninit() {
        Tools.loadData()
            .catch((error) => {
                ErrorHandler.show(error)
            })
    },
    view() {
        return [
            m("h1", t("Tools")),
            this.folderSectionView(),
            this.configSectionView()
        ]
    },
    folderSectionView() {
        return [
            m("h3", t("Folders")),
            m("p", t("The following folders can be emptied.")),
            m("table.pure-table.pure-table-horizontal", [
                m("tbody", Tools.files.map((file) => {
                    return m("tr", [
                        m("td[width=30%]", file.label),
                        m("td[width=40%]", file.alias),
                        m("td", [
                            m("span.count", file.count),
                            " ",
                            t("file(s)")
                        ]),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                Tools.emptyFolder(file.alias)
                                    .catch((error) => {
                                        ErrorHandler.show(error)
                                    });
                            },
                            "data-file": file.alias
                        }, t("Empty")))
                    ])
                }))
            ])
        ]
    },
    configSectionView() {
        if (Tools.configs.length === 0) {
            return [];
        }
        return [
            m("h3", t("Configuration")),
            m("p", t("The following YAML configuration files can be reformatted.")),
            m("table.pure-table.pure-table-horizontal", [
                m("tbody", Tools.configs.map((file) => {
                    return m("tr", [
                        m("td[width=30%]", file.label),
                        m("td", file.alias),
                        m("td[width=1%]", m("button.pure-button button-small", {
                            onclick: (e) => {
                                Tools.formatConfig(file.alias)
                                    .catch((error) => {
                                        ErrorHandler.show(error)
                                    });
                            },
                            "data-file": file.alias
                        }, t("Format")))
                    ])
                }))
            ]),
        ]
    }
}
