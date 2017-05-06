<?php

header('Access-Control-Allow-Origin: *');

if(isset($_GET['action'])) {
    $file = fopen("file.json", "r");
    $data = fread($file, 10000);
    fclose($file);
    $msg = "";
    $jsonData = json_decode($data, true);
    switch($_GET['action']) {
        case "read":
            $user = $_GET["user"];
            $msg = $jsonData[$user]["fav"];
            break;
        case "write":
            $file = fopen("file.json", "w");
            $user = $_GET["user"];
            $fav = $_GET["fav"];
            echo $fav;
            $jsonData[$user]["fav"] = $fav;
            fwrite($file, json_encode($jsonData, true));
            fclose($file);
            break;
        case "register":
            $file = fopen("file.json", "w");
            $user = $_GET["user"];
            $password = $_GET["password"];
            $jsonData[$user] = array("user" => $user, "password" => $password, "fav" => "", "visitCount" => 0, "lastVisit" => "");
            fwrite($file, json_encode($jsonData, true));
            fclose($file);
            $msg = "true";
            break;
        case "login":
            $user = $_GET["user"];
            $password = $_GET["password"];
            if(isset($jsonData[$user])) {
                if($jsonData[$user]["user"] == $user && $jsonData[$user]["password"] == $password) {
                    $msg = "true";
                } else {
                    $msg = "false";
                }
            } else {
                $msg = "false";
            }
            break;
        default:
            $msg = "test";
            break;
    }
    echo $msg;
    return;
}
?>

<!DOCTYPE HTML>
<HTML>
    <header>
        <title>News Feed</title>
        <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
        <script>
            var httpClient = function () {
                this.get = function (url, callback) {
                    var request = new XMLHttpRequest();
                    request.onreadystatechange = function () {
                        if(request.readyState == 4 && request.status == 200) {
                            callback(request.responseText);
                        }
                    }
                    request.open("GET", url, true);
                    request.send(null);
                }
            }
            function init() {
                if(readCookie("user") != "") {
                    document.getElementById("news").style.display = "block";
                    document.getElementById("login").style.display = "none";
                    var c = new httpClient();
                    c.get("./index.php?action=read&user=" + readCookie("user"), function(response) {
                        sessionStorage.setItem("user", readCookie("user"));
                        sessionStorage.setItem("fav", response);
                        writeCookie("fav", response);
                    });
                }
                else if(sessionStorage.user != undefined) {
                    var c = new httpClient();
                    c.get("./index.php?action=read&user=" + sessionStorage.user, function(response) {
                        sessionStorage.setItem("fav", response);
                        writeCookie("fav", response);
                    });
                }
            }
            function readCookie(name) {
                name = name + "=";
                var ca = document.cookie.split(';');
                for(var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') {
                        c = c.substring(1);
                    }
                    if (c.indexOf(name) == 0) {
                        return c.substring(name.length, c.length);
                    }
                }
                return "";
            }
            function writeCookie(name, data) {
                var d = new Date();
                d.setTime(d.getTime() + (1 * 24 * 60 * 60 * 1000));
                var expires = "expires="+d.toUTCString();
                document.cookie = name + "=" + data + ";" + expires + ";path=/";
            }
            function deleteCookie(name) {
                document.cookie = name + "=;" + " expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
            }
            function login() {
                var user = document.getElementsByName("username")[0].value;
                var password = document.getElementsByName("password")[0].value;
                var c = new httpClient();
                c.get("./index.php?action=login&user=" + user + "&password=" + password, function(response) {
                    if(response == "true") {
                        sessionStorage.setItem("user", user);
                        writeCookie("user", user);
                        init();
                        document.getElementById("news").style.display = "block";
                        document.getElementById("login").style.display = "none";
                    } else {
                        document.getElementById("news").style.display = "none";
                        document.getElementById("login").style.display = "block";
                        document.getElementById("reg").style.display = "block";
                        document.getElementById("reg").innerHTML = "Login fail";
                    }
                });
            }
            function register() {
                var user = document.getElementsByName("regusername")[0].value;
                var password = document.getElementsByName("regpassword")[0].value;
                sessionStorage.setItem("user", user);
                writeCookie("user", user);
                var c = new httpClient();
                c.get("./index.php?action=register&user=" + user + "&password=" + password, function(response) {
                    if(response == "true") {
                        document.getElementById("reg").innerHTML = "You are registered!";
                        document.getElementById("reg").style.display = "block";
                    } else {
                        document.getElementById("reg").innerHTML = "Something went wrong.";
                        document.getElementById("reg").style.display = "block";
                    }
                });
                return false;
            }
            function logout() {
                deleteCookie("user");
                deleteCookie("fav");
                sessionStorage.clear();
                document.getElementsByName("username")[0].value = "";
                document.getElementsByName("password")[0].value = "";
                document.getElementsByName("regusername")[0].value = "";
                document.getElementsByName("regpassword")[0].value = "";
                document.getElementById("news").style.display = "none";
                document.getElementById("login").style.display = "block";
                document.getElementById("reg").style.display = "block";
                document.getElementById("reg").innerHTML = "";
                document.getElementById("fav").checked = 0;
                document.getElementById("checkBCC").checked = 0;
                document.getElementById("checkScience").checked = 0;
                document.getElementById("checkTech").checked = 0;
                document.getElementById("checkWeather").checked = 0;
                document.getElementById("checkSport").checked = 0;
                document.getElementById("list").innerHTML = "";
                //window.location = "../index.php";
            }
            function news() {
                document.getElementById("list").innerHTML = "";
                $("#list").append("<ul id='newList'></ul>");
                var fav = $("#fav").is(':checked');
                var bcc = $("#checkBCC").is(':checked');
                var science = $("#checkScience").is(':checked');
                var tech = $("#checkTech").is(':checked');
                var weather = $("#checkWeather").is(':checked');
                var sport = $("#checkSport").is(':checked');
                if(bcc) {
                    $.get("http://feeds.bbci.co.uk/news/world/rss.xml", function (data) {
                        $(data).find("item").each(function () {
                            var el = $(this);
                            var title = el.find("title").text();
                            var firstTitle = title.split(' ')[0] + title.split(' ')[1];
                            var desc = el.find("description").text();
                            var link = el.find("link").text();
                            var ary = {};
                            if(sessionStorage.fav != "") {
                                ary = JSON.parse(sessionStorage.fav);
                            }
                            var isFav = (firstTitle in ary);
                            if(fav && isFav) {
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\" checked></li>");
                            } else if(!fav) {
                                var extra = "";
                                if(isFav) {
                                    extra = " checked";
                                }
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\""+extra+"></li>");
                            }
                        })
                    });
                }
                if(science) {
                    $.get("https://rss.sciencedaily.com/matter_energy/engineering.xml", function (data) {
                        $(data).find("item").each(function () {
                            var el = $(this);
                            var title = el.find("title").text();
                            var firstTitle = title.split(' ')[0] + title.split(' ')[1];
                            var desc = el.find("description").text();
                            var link = el.find("link").text();
                            var ary = {};
                            if(sessionStorage.fav != "") {
                                ary = JSON.parse(sessionStorage.fav);
                            }
                            var isFav = (firstTitle in ary);
                            if(fav && isFav) {
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\" checked></li>");
                            } else if(!fav) {
                                var extra = "";
                                if(isFav) {
                                    extra = " checked";
                                }
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\""+extra+"></li>");
                            }
                        })
                    });
                }
                if(tech) {
                    $.get("http://feeds.bbci.co.uk/news/technology/rss.xml", function (data) {
                        $(data).find("item").each(function () {
                            var el = $(this);
                            var title = el.find("title").text();
                            var firstTitle = title.split(' ')[0] + title.split(' ')[1];
                            var desc = el.find("description").text();
                            var link = el.find("link").text();
                            var ary = {};
                            if(sessionStorage.fav != "") {
                                ary = JSON.parse(sessionStorage.fav);
                            }
                            var isFav = (firstTitle in ary);
                            if(fav && isFav) {
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\" checked></li>");
                            } else if(!fav) {
                                var extra = "";
                                if(isFav) {
                                    extra = " checked";
                                }
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\""+extra+"></li>");
                            }
                        })
                    });
                }
                if(weather) {
                    $.get("https://www.yahoo.com/news/rss/weather/", function (data) {
                        console.log(data);
                        $(data).find("item").each(function () {
                            var el = $(this);
                            var title = el.find("title").text();
                            var firstTitle = title.split(' ')[0] + title.split(' ')[1];
                            var desc = el.find("description").text();
                            var link = el.find("link").text();
                            var ary = {};
                            if(sessionStorage.fav != "") {
                                ary = JSON.parse(sessionStorage.fav);
                            }
                            var isFav = (firstTitle in ary);
                            if(fav && isFav) {
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\" checked></li>");
                            } else if(!fav) {
                                var extra = "";
                                if(isFav) {
                                    extra = " checked";
                                }
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\""+extra+"></li>");
                            }
                        })
                    });
                }
                if(sport) {
                    $.get("http://www.bbc.com/sport/rss.xml", function (data) {
                        console.log(data);
                        $(data).find("item").each(function () {
                            var el = $(this);
                            var title = el.find("title").text();
                            var firstTitle = title.split(' ')[0] + title.split(' ')[1];
                            var desc = el.find("description").text();
                            var link = el.find("link").text();
                            var ary = {};
                            if(sessionStorage.fav != "") {
                                ary = JSON.parse(sessionStorage.fav);
                            }
                            var isFav = (firstTitle in ary);
                            if(fav && isFav) {
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\" checked></li>");
                            } else if(!fav) {
                                var extra = "";
                                if(isFav) {
                                    extra = " checked";
                                }
                                $("#newList").append("<li>Title: "+title
                                +"</li><li>Description: "+desc
                                +"</li><li><a href='"+link
                                +"'>Link</a><input type='checkbox' id="+firstTitle+" onchange=\"favNew('"+firstTitle
                                +"')\""+extra+"></li>");
                            }
                        })
                    });
                }
            }
            function favNew(name) {
                var ary = {};
                if(sessionStorage.fav != "") {
                    ary = JSON.parse(sessionStorage.fav);
                }
                if($("#" + name).is(':checked')) {
                    ary[name] = "";
                } else {
                    delete ary[name];
                }
                var str = JSON.stringify(ary);
                sessionStorage.setItem("fav", str);
                writeCookie("fav", str);
                var c = new httpClient();
                c.get("./index.php?action=write&user=" + sessionStorage.user + "&fav=" + str, function(response) {});
                news();
            }
        </script>
        <style>
            #login form {
                border-style: solid;
                border-width: 1px;
                border-radius: 5px;
                margin-bottom: 50px;
                text-align: center;
            }
            #login form input { margin-bottom: 5px; }
            #login form button { margin-bottom: 10px; }
            #login form h2 { margin: 5px; }
            #news {
                display: none;
                text-align: center;
            }
            #reg { display: none; text-align: center; }
            ul { list-style: none; }
            li { margin-top: 10px; margin-bottom: 10px; }
        </style>
    </header>
    <body onload="init()">
        <div id="login">
            <form onsubmit="return false;">
                <h2>Login</h2>
                <label for="username">Username</label><br>
                <input type="text" id="username" name="username" required><br>
                <label for="passowrd">Password</label><br>
                <input type="password" id="password" name="password" required><br>
                <button onclick="login()">Login</button>
            </form>
            <form onsubmit="return false;">
                <h2>Register</h2>
                <label for="regusername">Username</label><br>
                <input type="text" id="regusername" name="regusername" required><br>
                <label for="regpassowrd">Password</label><br>
                <input type="password" id="regpassowrd" name="regpassword" required><br>
                <button onclick="register()">Register</button>
            </form>
            <h3 id="reg"></h3>
        </div>
        <div id="news">
            <h2>News Feed</h2>
            <button onclick="logout()">Logout</button><br><br>
            <input type="checkbox" id="fav" onchange="news()">Favorite</input>
            <input type="checkbox" id="checkBCC" onchange="news()">BCC</input>
            <input type="checkbox" id="checkScience" onchange="news()">ScienceDaily</input>
            <input type="checkbox" id="checkTech" onchange="news()">Technology</input>
            <input type="checkbox" id="checkWeather" onchange="news()">Weather</input>
            <input type="checkbox" id="checkSport" onchange="news()">Sport</input>
            <div id="list">
                
            </div>
        </div>
    </body>
</HTML>