.PHONY: build push help pull_all deploy

help:
	@echo "build usage: make build"
	@echo "push usage: make push BRANCH=master BUILD_NUMBER=Number"
	
push:
	@echo "Publishing data container"
	docker tag registry.snaprapid.com/dashboards_api_src:latest registry.snaprapid.com/dashboards_api_src:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_api_src:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_api_src:latest
	@echo "Publishing php-fpm container"
	docker tag registry.snaprapid.com/dashboards_backend_php:latest registry.snaprapid.com/dashboards_backend_php:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_backend_php:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_backend_php:latest
	@echo "Publishing php-nginx container"
	docker tag registry.snaprapid.com/dashboards_api:latest registry.snaprapid.com/dashboards_api:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_api:$(BRANCH).$(BUILD_NUMBER)
	docker push registry.snaprapid.com/dashboards_api:latest

# Usage: 
build:
	docker-compose -f docker/production/docker-compose.yml build

pull_all:
	docker pull registry.snaprapid.com/dashboards_api:latest
	docker pull registry.snaprapid.com/dashboards_backend_php:latest
	docker pull registry.snaprapid.com/dashboards_api_src:latest

deploy:
	chmod +x ./docker/production/deploy.sh
	./docker/production/deploy.sh


# Run local.
# docker run -d -v /var/www/symfony --name data registry.snaprapid.com/dashboards_api_src
# docker run -d --volumes-from data --name php registry.snaprapid.com/dashboards_backend_php
# docker run -d --volumes-from data -p 80:80 --link php:php registry.snaprapid.com/dashboards_api
# docker run -d -p 90:80 registry.snaprapid.com/dashboards_web
