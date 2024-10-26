include $(TOPDIR)/rules.mk

LUCI_TITLE:=LuCI RTA-WRT Injector App
LUCI_PKGARCH:=all
LUCI_DEPENDS:=+php8 +php8-cgi +php8-mod-session +zoneinfo-core +zoneinfo-asia +sshpass \
			+openvpn-openssl +ip-full +openssh-client +httping +screen +python3 +coreutils-nohup +libevent2 +redsocks \
			+badvpn +corkscrew


PKG_NAME:=luci-app-rtawrt-injector
PKG_VERSION:=1.0.0
PKG_RELEASE:=beta

define Package/$(PKG_NAME)
	$(call Package/luci/webtemplate)
	TITLE:=$(LUCI_TITLE)
	DEPENDS:=$(LUCI_DEPENDS)
endef

define Package/$(PKG_NAME)/description
	LuCI version of RTA-WRT Injector, but with some mods and additions.
endef

define Package/$(PKG_NAME)/install
	$(INSTALL_DIR) $(1)/usr/lib/lua/luci
	cp -pR ./luasrc/* $(1)/usr/lib/lua/luci
	$(INSTALL_DIR) $(1)/
	cp -pR ./root/* $(1)/
	chmod -R 755 /root/www/rtawrt-injector/*
	chmod -R 755 /root/usr/share/rtawrt-injector/*
endef

define Package/$(PKG_NAME)/postinst
#!/bin/sh
	[ -d /tmp/luci-modulecache ] && rm -rf /tmp/luci-modulecache
	find /tmp -type f -name 'luci-indexcache.*' -exec rm -f {} \;
	chmod -R 755 /usr/lib/lua/luci/controller/*
	chmod -R 755 /usr/lib/lua/luci/view/*
	chmod -R 755 /www/rtawrt-injector/*
	chmod -R 755 /usr/share/rtawrt-injector/*
	sed -i '3 i /usr/sbin/openvpn --mktun --dev tun1' /etc/rc.local
	# Autofix download index.php, index.html
	if ! grep -q ".php=/usr/bin/php-cgi" /etc/config/uhttpd; then
		echo -e "  RTA-WRT : system not using php-cgi, patching php config ..."
		logger "  RTA-WRT : system not using php-cgi, patching php config..."
		uci set uhttpd.main.ubus_prefix='/ubus'
		uci set uhttpd.main.interpreter='.php=/usr/bin/php-cgi'
		uci set uhttpd.main.index_page='cgi-bin/luci'
		uci add_list uhttpd.main.index_page='index.html'
		uci add_list uhttpd.main.index_page='index.php'
		uci commit uhttpd
		echo -e "  RTA-WRT : patching system with php configuration done ..."
		echo -e "  RTA-WRT : restarting some apps ..."
		logger "  RTA-WRT : patching system with php configuration done..."
		logger "  RTA-WRT : restarting some apps..."
		/etc/init.d/uhttpd restart
	fi
	/usr/sbin/openvpn --mktun --dev tun1
	[ -d /usr/lib/php8 ] && [ ! -d /usr/lib/php ] && ln -sf /usr/lib/php8 /usr/lib/php
exit 0
endef

define Package/$(PKG_NAME)/postrm
#!/bin/sh
	export NAMAPAKET="rtawrt-injector"
	if [ -d /www/$NAMAPAKET ] ; then
		rm -rf /www/$NAMAPAKET
		rm -rf /usr/share/$NAMAPAKET
	fi
	unset NAMAPAKET
exit 0
endef

include $(TOPDIR)/feeds/luci/luci.mk

$(eval $(call BuildPackage,$(PKG_NAME)))
