.PHONY: build

build:
	box build -c box.svscale.json

install: build
	cp builds/svscale.phar ${HOME}/bin/svscale
	chmod +x ${HOME}/bin/svscale
