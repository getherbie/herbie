import m from "mithril";

let Test = {
    list: [],
    loadList() {
        return m.request({
            method: "GET",
            url: WEB_URL + "/adminpanel/test"
        }).then((result) => {
            Test.list = result
        })
    },
    add(entry) {
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/test/add",
            data: entry
        }).then(() => {
            Test.list.push(entry)
        });
    },
    remove(index) {
        return m.request({
            method: "DELETE",
            url: WEB_URL + "/adminpanel/test/" + index
        }).then(() => {
            Test.list.splice(index, 1)
        })
    }
};

export default Test;
