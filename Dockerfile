FROM centos:centos6

MAINTAINER manuel.vacelet@enalean.com

RUN yum -y update && yum clean all
RUN yum -y install php && yum clean all
RUN yum -y install php-dom && yum clean all

RUN rpm -i http://mir01.syntis.net/epel/6/i386/epel-release-6-8.noarch.rpm
RUN yum -y install php-pecl-xdebug && yum clean all

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer

# RPMs
RUN yum -y install rpm-build && yum clean all
RUN yum -y install tar && yum clean all

RUN yum -y install git && yum clean all

RUN useradd builder
RUN mkdir /output

# Make composer cache first
# Add of src will wipe composer cache stuff
# So we pre-create it so we save ticks on run
ADD composer.json /src/composer.json
RUN /usr/local/bin/composer --working-dir=/src install

ADD . /src
RUN chown -R builder.builder /src /output

USER builder
WORKDIR /src
CMD [ "make" ]
