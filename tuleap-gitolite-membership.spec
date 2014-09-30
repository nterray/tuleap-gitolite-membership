Summary: Tuleap/Gitolite membership retriever
Name: tuleap-gitolite-membership
Version: @@VERSION@@
Release: @@RELEASE@@%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{name}-%{version}.tar.gz
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
Packager: Manuel VACELET <manuel.vacelet@enalean.com>

AutoReqProv: no

Requires: php(language) >= 5.3

%description
Tuleap/Gitolite membership retriever

# 
# Package setup
%prep
%setup -q

#
# Build
%build
# Nothing to do

#
# Install
%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT%{_datadir}/%{name}
%{__install} -m 700 -d $RPM_BUILD_ROOT%{_localstatedir}/cache/%{name}
%{__install} -d $RPM_BUILD_ROOT%{_sysconfdir}
%{__install} config.ini $RPM_BUILD_ROOT%{_sysconfdir}/%{name}.ini
%{__cp} -ar * $RPM_BUILD_ROOT%{_datadir}/%{name}

%pre
if [ "$1" -eq "1" ]; then
    # Install
    true
else
    # Update
    true
fi

%clean
%{__rm} -rf $RPM_BUILD_ROOT

#
#
#
%files
%defattr(-,root,root,-)
%{_datadir}/%{name}
%attr(0700,gitolite,gitolite) %{_localstatedir}/cache/%{name}
%attr(0600,gitolite,gitolite) %config(noreplace) %{_sysconfdir}/%{name}.ini

%changelog
* Tue Sep 30 2014 Manuel VACELET <manuel.vacelet@enalean.com> -
- First package
