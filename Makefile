.PHONY: build run stop logs

build:
	sudo docker build -t csr-membership-app .

run:
	sudo docker rm -f csr-membership-app || true
	sudo docker run -d --name csr-membership-app -p 8000:80 -v $(shell pwd):/var/www/html csr-membership-app

stop:
	sudo docker stop csr-membership-app

logs:
	sudo docker logs -f csr-membership-app
