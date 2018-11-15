#!/bin/bash
#
short_name="e20r-tracker"
remote_server="eighty20results.com"
include=(blocks classes css img js languages ${short_name}.php e20r_db_update.php README.txt)
exclude=(vendor *.yml *.phar composer.*)
build=(classes/plugin-updates/vendor/*.php)
plugin_path="${short_name}"
readme_path="../build_readmes/"
changelog_source=${readme_path}current.txt
meta_log_source=${readme_path}existing_json.txt
readme_source=${readme_path}existing_readme.txt
json_template="metadata.json.template"
readme_template="README.txt.template"
readme_txt="README.txt"
readme_json="metadata.json"
metadata="../metadata.json"
version=$(egrep "^Version:" ../${short_name}.php | sed 's/[[:alpha:]|(|[:space:]|\:]//g' | awk -F- '{printf "%s", $1}')
src_path="../"
dst_path="../build/${plugin_path}"
kit_path="../build/kits"
kit_name="${kit_path}/${short_name}-${version}"

echo "Building kit for version ${version}"

mkdir -p ${kit_path}
mkdir -p ${dst_path}

if [[ -f  ${kit_name} ]]
then
    echo "Kit is already present. Cleaning up"
    rm -rf ${dst_path}
    rm -f ${kit_name}
fi

for p in ${include[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

for e in ${exclude[@]}; do
    find ${dst_path} -name ${e} -exec rm -rf {} \;
done

mkdir -p ${dst_path}/classes/plugin-updates/vendor/
for b in ${build[@]}; do
    cp ${src_path}${b} ${dst_path}/classes/plugin-updates/vendor/
done

cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
ssh ${remote_server} "cd ./www/protected-content/ ; mkdir -p \"${short_name}\""
scp ${kit_name}.zip ${remote_server}:./www/protected-content/${short_name}/
scp ${metadata} ${remote_server}:./www/protected-content/${short_name}/
ssh ${remote_server} "cd ./www/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}
