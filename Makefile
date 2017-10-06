.PHONY: build

build:
	box build -c box.lnscale.json

install: build
	cp builds/lnscale.phar ${HOME}/bin/lnscale
	chmod +x ${HOME}/bin/lnscale
