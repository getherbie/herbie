import m from "mithril";

let Auth = {
    username: "",
    password: "",

    setUsername: function(value) {
        Auth.username = value
    },
    setPassword: function(value) {
        Auth.password = value
    },
    login: function() {
        m.request({
            method: "POST",
            url: WEB_URL + "/adminpanel/auth",
            data: {username: Auth.username, password: Auth.password}
        }).then(function(data) {
            localStorage.setItem("auth-token", data.token)
            m.route.set("/")
        })
    },
    logout: function() {
        Auth.username = '';
        Auth.password = '';
        localStorage.removeItem("auth-token");
    }
}

export default Auth;
