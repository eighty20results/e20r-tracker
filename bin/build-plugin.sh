#!/bin/bash
#
INCLUDE=(classes css images js languages e20r-tracker.php README.txt)
short_name="e20r-tracker"
plugin_path="${short_name}"
version=$(grep Version ../${short_name}.php | awk '{print $2}')
src_path="../"
dst_path="../build/${plugin_path}-${version}/"

mkdir -p ${dst_path} 

for p in ${INCLUDE[@]}; do
	cp -R ${src_path}${p} ${dst_path}
done

cd ${dst_path}/..
zip -r ${short_name}-${version}.zip ${short_name}-${version}

