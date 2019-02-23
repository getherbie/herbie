import m from "mithril";

let Media = {
    currentDir: "",
    list: [],
    loadList() {
        return m.request({
            method: "GET",
            url: WEB_URL + "/adminpanel/media",
            data: {dir: this.currentDir}
        }).then((result) => {
            Media.list = result
        })
    },
    add(name) {
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/media/addfolder",
            data: name
        }).then((entry) => {
            Media.list.entries.push(entry)
        });
    },
    removeFolder(index) {
        return m.request({
            method: "DELETE",
            url: WEB_URL + "/adminpanel/media/deletefolder",
            data: {folder: Media.list.entries[index].path}
        }).then(() => {
            Media.list.entries.splice(index, 1)
        })
    },
    removeFile(index) {
        return m.request({
            method: "DELETE",
            url: WEB_URL + "/adminpanel/media/deletefile",
            data: {file: Media.list.entries[index].path}
        }).then(() => {
            Media.list.entries.splice(index, 1)
        })
    },
    uploadFile(file) {
        var data = new FormData()
        data.append("uploadFile", file)
        data.append('currentDir', this.currentDir)
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/media/uploadfile",
            data: data,
        }).then((entry) => {
            Media.list.entries.push(entry)
        })
    }
};

export default Media;
