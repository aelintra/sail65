BEGIN TRANSACTION;

INSERT OR IGNORE INTO Route(pkey,active,auth,cluster,desc,dialplan,path1,path2,path3,path4) values ('DEFAULT','YES','NO','default','DEFAULT TRUNK','_XXXX.','None','None','None','None');
INSERT OR IGNORE INTO lineIO(pkey,active,carrier,closeroute,cluster,desc,faxdetect,lcl,moh,monitor,openroute,peername,routeclassopen,routeclassclosed,swoclip,technology) values ('_XXXX.','YES','PTT_DiD_Class','Operator','default','user1804','NO','NO','NO','NO','Operator','peer1798','100','100','NO','Class');
INSERT OR IGNORE INTO speed(pkey,cluster,devicerec,grouptype,outcome,outcomerouteclass,ringdelay) values ('RINGALL','default','default','Ring','Operator','100','180');
INSERT OR IGNORE INTO users (id,name,email,password,role) VALUES ('1','admin','admin@pbx3.com','$2y$12$IHbfUfGA3TOj2hnGld7TM.2gMqhyvQnWoAVmMwX3N5Uo7WNvaW85K','isAdmin');
COMMIT;
