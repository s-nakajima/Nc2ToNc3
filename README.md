Nc2ToNc3
==============

Migration plugin to Nc3 from Nc2 for NetComomns3

## 説明

```
cakeコマンドのパス/cake Nc2ToNc3 --database NC2のDB名 --prefix NC2のテーブルプレフィックス名 --upload_path NC2のアップロードファイルフォルダーパス --base_url NC2のベースURL --nc3base NC3のベースパス
```
Ex.)
```
./Console/cake Nc2ToNc3 --database nc2421 --prefix nc_ --upload_path /var/www/html/NC2/html/webapp/uploads/ --base_url http://example.com/NC2/html --nc3base /nc3
```

シェルからの実行は、現状NC2のダンプファイルをNC3のDBと同じ環境にインポートして実行してください。

~~CakePHPのMigrationを実行すると、管理画面に「NC2からの移行」メニューが追加され、画面から実行可能になります。~~
~~画面からの実行は別環境のDBへも接続可能です。~~

開発中につき、必ずNc3のDB、および、NC3のアップロードファイルをバックアップして、いつでもリストアできるようにしてから実行してください。


nc2_to_nc3_mapsテーブルを修正しました。  
map→nc3_id  
nc2_to_nc3_mapsを一度削除して、再度Migrationを実行してください。  
```
cakeコマンドのパス/cake Migrations.migration run -p Nc2ToNc3  -c master -i master
```

