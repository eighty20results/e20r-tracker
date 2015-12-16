#!/bin/bash
#
include=(classes css img js languages e20r-tracker.php e20r_db_update.php README.txt)
exclude=(vendor *.yml *.phar composer.*)
short_name="e20r-tracker"
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
version=$(egrep "^Version:" ../${short_name}.php | awk '{print $2}')
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

#if [[ -f ${readme_path}${json_template} ]]
#then
#    echo "Building metadata.json file"
#    meta_log=$(sed -e"s/\"/\'/g" -e"s/.*/\<li\>&\<\/li\>/" ${changelog_source} )
#    #history=$(cat ${meta_log_source})
#    to_file="\<h3\>${version}\<\/h3\>\<ol\>${meta_log}\<\/ol\>$(cat ${meta_log_source})"
#    fix_line=$(echo ${meta_log} | tr -d '\n')
#    sed -e "s/\[JSON_LOG\]/$to_file" -e "s/\[VERSION\]/$version" ${json_template} > ${metadata}
#fi

#if [[ -f ${readme_path}${readme_template} ]]
#then
#    echo "Building metadata.json file"
#    meta_log=$(sed -e"s/\"/\'/g" -e"s/.*/\*\ &/" ${changelog_source} )
#    #history=$(cat ${readme_source})
#    header="== ${version} =="
#    content="${meta_log}"
#    new=$(cat ${readme_source})
#    all="${header}\n\n{$content}${new}"
#    sed -e "s/\[MARKDOWN_LOG\]/${all}}" -e "s/\[VERSION\]/${version}/" ${readme_template} > ${readme_txt}
#fi

for p in ${include[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

for e in ${exclude[@]}; do
    find ${dst_path} -name ${e} -exec rm -rf {} \;
done

cd ${dst_path}/..
zip -r ${kit_name}.zip ${plugin_path}
scp ${kit_name}.zip siteground-e20r:./www/protected-content/e20r-tracker/
scp ${metadata} siteground-e20r:./www/protected-content/e20r-tracker/
ssh siteground-e20r "cd ./www/protected-content/ ; ln -sf \"${short_name}\"/\"${short_name}\"-\"${version}\".zip \"${short_name}\".zip"
rm -rf ${dst_path}
