current_working_dir=`pwd`
rm -rf ./filegator-zip/filegator
mkdir ./filegator-zip/filegator
rsync -av --progress ./filegator ./filegator-zip/ --exclude node_modules
cd ./filegator-zip
zip -r ./filegator.zip ./
rm -rf ./filegator
cd $current_working_dir
docker-compose down && docker-compose build --no-cache && docker-compose up

# unzip ./filegator.zip -d ./