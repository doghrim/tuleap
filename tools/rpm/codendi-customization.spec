# Platform variables
%define CODENDI_PLATFORM @@PLATFORM@@
%define SYS_DEFAULT_DOMAIN @@SYS_DEFAULT_DOMAIN@@
%define SYS_HTTPS_HOST @@SYS_HTTPS_HOST@@

# Define variables
%define PKG_NAME @@PKG_NAME@@
%define APP_NAME codendi
%define APP_DIR %{_datadir}/%{APP_NAME}

Summary: Tuleap customization for @@PLATFORM@@ platform
Name: %{PKG_NAME}-customization-@@PLATFORM@@
Provides: %{PKG_NAME}-customization
Version: @@VERSION@@
Release: 1%{?dist}
BuildArch: noarch
License: GPL
Group: Development/Tools
URL: http://tuleap.net
Source0: %{PKG_NAME}-%{version}.tar.gz
Source1: cli_ParametersLocal.dtd
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root
BuildRequires: zip libxslt

%description
This package provides the documentation, CLI package and themes modifications
that customize the Tuleap application for "@@PLATFORM@@" platform.

%prep
%setup -q -n %{PKG_NAME}-%{version}

%build
cat > local.inc <<EOF
\$codendi_documentation_prefix = "$PWD/documentation";
\$codendi_dir = "$PWD";
\$tmp_dir = "$RPM_BUILD_ROOT";
\$sys_default_domain = "%{SYS_DEFAULT_DOMAIN}";
\$sys_https_host = "%{SYS_HTTPS_HOST}";
\$codendi_downloads_dir = "$PWD/downloads";
EOF

%{__cp} %{SOURCE1} cli_ParametersLocal.dtd

tools/rpm/build_release.sh

%install
%{__rm} -rf $RPM_BUILD_ROOT

# Doc: CLI
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli
%{__cp} -ar documentation/cli/html $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli
%{__cp} -ar documentation/cli/icons $RPM_BUILD_ROOT/%{APP_DIR}/documentation/cli

# CLI package
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/downloads
%{__cp} -ar downloads/* $RPM_BUILD_ROOT/%{APP_DIR}/downloads

# Custom logo
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{APP_DIR}/src/www/themes/common/images

%post
/usr/bin/chcon -R root:object_r:httpd_sys_content_t %{APP_DIR}/documentation %{APP_DIR}/downloads || true


%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{APP_DIR}/documentation
%{APP_DIR}/downloads

#%doc
#%config



%changelog
* Thu Jun  3 2010 Manuel VACELET <manuel.vacelet@st.com> - 
- Initial build.

