module("luci.controller.rtawrt-injector", package.seeall)
function index()
entry({"admin","services","rtawrt-injector"}, template("rtawrt-injector"), _("RTAWRT Injector"), 11).leaf=true
end