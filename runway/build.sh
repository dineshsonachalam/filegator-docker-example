current_working_dir=`pwd`
rm -rf ./filegator-zipped-folder/filegator
mkdir ./filegator-zipped-folder/filegator
rsync -av --progress ./filegator ./filegator-zipped-folder/ --exclude node_modules
cd ./filegator-zipped-folder
zip -r ./filegator.zip ./
rm -rf ./filegator
cd $current_working_dir
docker-compose down && docker-compose build --no-cache && docker-compose up

# unzip ./filegator.zip -d ./