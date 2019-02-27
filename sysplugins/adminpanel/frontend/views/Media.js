import m from "mithril";
import Media from "../models/Media";
import ErrorHandler from "../components/ErrorHandler";

export default {
    folderName: '',
    oninit() {
        Media.loadList()
            .catch((error) => {
                ErrorHandler.show(error)
            })
    },
    view() {
        return [
            m("h1", "Dateiablage"),
            this.renderTable()
        ];
    },
    renderTable() {
        if (Media.list.length === 0) {
            return m("div", "No test entries");
        }
        return [
            m("div.media__forms", [
                this.createFolderForm(),
                this.uploadFileForm(),
            ]),
            m("table.pure-table.pure-table-horizontal.media__table", [
                m("thead", [
                    m("tr", [
                        m("th.icon[width=1%]"),
                        m("th.title[width=55%]", "Datei"),
                        m("th[width=20%]", "Grösse"),
                        m("th[width=20%]", "Art"),
                        m("th[width=4%]"),
                    ])
                ]),
                m("tbody#test-tbody", [
                    this.renderNavigationRow(Media.list),
                    Media.list.entries.map((test, index) => {
                        if (test.type === 'dir') {
                            return this.renderDirRow(test, index)
                        }
                        return this.renderFileRow(test, index)
                    })
                ])
            ])
        ]
    },
    renderDirRow(test, index) {
        return [
            m("tr", [
                m("td.icon", [
                    m("i.fa.fa-folder-o.fa-lg")
                ]),
                m("td.title", [
                    m("a[href='#']", {
                        onclick: (e) => {
                            e.preventDefault()
                            //m.route.set("/media", { dir: test.path})
                            Media.currentDir = test.path;
                            Media.loadList()
                                .catch((error) => {
                                    ErrorHandler.show(error)
                                })
                        }
                    }, test.name)
                ]),
                m("td", test.size),
                m("td", test.ext),
                m("td", [
                    m("button.pure-button.button-small", {
                        onclick: (e) => {
                            this.removeDir(index)
                        }
                    }, "Delete")
                ])
            ])
        ]
    },
    renderFileRow(test, index) {
        return [
            m("tr", [
                m("td.icon", [
                    m("i.fa.fa-file-o.fa-lg")
                ]),
                m("td.title", test.name),
                m("td", test.size),
                m("td", test.ext),
                m("td", [
                    m("button.pure-button.button-small", {
                        onclick: (e) => {
                            this.removeFile(index)
                        }
                    }, "Delete")
                ])
            ])
        ]
    },
    createFolderForm() {
        return m("div.pure-form.form-add-folder", [
            m("input.name[type=text][name=name]", {
                oninput: (e) => {
                    this.folderName = e.target.value
                    e.redraw = false
                },
                /*onkeypress: (e) => {
                    this.error = ""
                    e.redraw = false
                },*/
                onkeyup: (e) => {
                    if (e.keyCode == 13) {
                        this.folderName = e.target.value
                        this.addFolder(this.folderName)
                    }
                    e.redraw = false
                },
                value: this.folderName,
            }),
            m("button.save.pure-button", {
                    onclick: (e) => {
                        this.addFolder(this.folderName)
                    }
                }, "Order erstellen"
            ),
            //m("button.cancel.pure-button", "Abbrechen"),
        ]);
    },
    uploadFileForm() {
        return m("div.pure-form.form-upload", [
            m("button.pure-button#fileselector", {
                onclick: (e) => {
                    document.getElementById("file_upload").click();
                },
                }, "Dateien hochladen"
            ),
            m("input#file_upload[type=file][name=file_upload]", {
                onchange: this.uploadFile
            })
        ]);
    },
    renderNavigationRow(media) {
        if (media.currentDir != "") {
            return m("tr", [
                m("td.icon", [
                    m("i.fa.fa-level-up.fa-lg")
                ]),
                m("td.title[colspan=4]", [
                    m("a[href='#']", {
                        onclick: (e) => {
                            e.preventDefault()
                            Media.currentDir = media.parentDir;
                            Media.loadList()
                                .catch((error) => {
                                    ErrorHandler.show(error);
                                })
                        }
                    }, "..")
                ]),
            ])
        }
    },
    removeDir(index) {
        if (confirm('Delete folder?')) {
            Media.removeFolder(index)
                .catch((error) => {
                    ErrorHandler.show(error);
                });
        }
    },
    removeFile(index) {
        if (confirm('Delete file?')) {
            Media.removeFile(index)
                .catch((error) => {
                    ErrorHandler.show(error);
                })
        }
    },
    addFolder(name) {
        Media.add({
            folderName: name,
            currentDir: Media.list.currentDir
        }).catch((error) => {
            ErrorHandler.show(error)
        })
        this.folderName = "";
    },
    uploadFile(e) {
        var file = e.target.files[0]
        Media.uploadFile(file)
            .catch((error) => {
                ErrorHandler.show(error)
            })
    }
}
