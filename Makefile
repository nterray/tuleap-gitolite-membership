DOCKER_IMAGE_NAME=tuleap-gitolite-membership

COMPOSER=/usr/local/bin/composer
OUTPUT_DIR=/output
PHPUNIT_TESTS_OPTIONS=--log-junit $(OUTPUT_DIR)/phpunit_tests.xml --coverage-html $(OUTPUT_DIR)/phpunit_coverage --coverage-clover $(OUTPUT_DIR)/phpunit_coverage/coverage.xml

RPM_TMP=/var/tmp/rpmbuild
PKG_NAME=tuleap-gitolite-membership
VERSION=$(shell LANG=C cat VERSION)
RELEASE=1
DIST=.el
BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)" --define "dist $(DIST)"

NAME_VERSION=$(PKG_NAME)-$(VERSION)

all: test rpm

world:
	docker build -t $(DOCKER_IMAGE_NAME) .
	docker rm -f $(JOB_NAME) || true
	time docker run --name=$(JOB_NAME) $(DOCKER_IMAGE_NAME)
	docker cp $(JOB_NAME):/output $(WORKSPACE)
	docker rm -f $(JOB_NAME)

composer_update:
	$(COMPOSER) update

test: composer_update
	vendor/bin/phpunit $(PHPUNIT_TESTS_OPTIONS)

rpm: composer_update $(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm
	@cp $(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm /output
	@echo "Results: $^"

$(RPM_TMP)/RPMS/noarch/%.noarch.rpm: $(RPM_TMP)/SRPMS/%.src.rpm
	$(RPMBUILD) --rebuild $<

$(RPM_TMP)/SRPMS/%-$(VERSION)-$(RELEASE)$(DIST).src.rpm: $(RPM_TMP)/SPECS/%.spec $(RPM_TMP)/SOURCES/%-$(VERSION).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$*.spec

$(RPM_TMP)/SPECS/%.spec: $(BASE_DIR)/%.spec
	cat $< | \
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $@

$(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz: $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(NAME_VERSION)
	cd $(RPM_TMP)/SOURCES && \
		find $(NAME_VERSION)/ \(\
                -path "$(NAME_VERSION)/tests" -o\
		-name '*.spec' -o\
		-name "phpunit.xml" -o\
		-name 'Makefile' -o\
		-name ".git" -o\
		-name ".gitignore" -o\
		-name ".gitmodules" -o\
		-name "*~" -o\
		-path "*/.DS_Store"-o\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP
