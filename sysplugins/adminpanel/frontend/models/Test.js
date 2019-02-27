import m from "mithril";

let Test = {
    list: [],
    loadList() {
        return m.request({
            method: "GET",
            url: WEB_URL + "/adminpanel/test",
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then((result) => {
            Test.list = result
        })
    },
    add(entry) {
        return m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/test/add",
            data: entry,
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then(() => {
            Test.list.push(entry)
        });
    },
    remove(index) {
        return m.request({
            method: "DELETE",
            url: WEB_URL + "/adminpanel/test/" + index,
            headers: {
                Authorization: 'Bearer ' + localStorage.getItem("auth-token")
            }
        }).then(() => {
            Test.list.splice(index, 1)
        })
    }
};

export default Test;
