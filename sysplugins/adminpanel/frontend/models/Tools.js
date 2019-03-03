import m from "mithril";

let Tools = {
    files: [],
    configs: [],
    loadData() {
        return m.request({
            method: "GET",
            url: WEB_URL + "/adminpanel/tools",
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then((result) => {
            Tools.configs = result.configs;
            Tools.files = result.files;
        })
    },
    emptyFolder(alias) {
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/tools/emptyfolder",
            data: { alias: alias },
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then(() => {
            let index = Tools.files.map(file => file.alias).indexOf(alias);
            if (index > -1) {
                Tools.files[index]['count'] = 0;
            }
        });
    },
    formatConfig(alias) {
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/tools/formatconfig",
            data: { alias: alias },
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then((entry) => {
            console.log(entry);
        });
    },
};

export default Tools;
