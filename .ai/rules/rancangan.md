---
paths:
  - 'D:/rancangan/**'
  - 'D:/rancangan/generate-uml.php'
  - 'D:/rancangan/generate-mdj.php'
---

# Rancangan

## MDJ ownedViews harus array list view murni (bukan wrapper/assoc)
Di D:\rancangan\generate-mdj.php, classViewData() mengembalikan wrapper ['view'=>...,'id'=>...]. Saat mengisi $diagram['ownedViews'], WAJIB simpan $v['view'] dan output via array_values($views) agar JSON berupa LIST of view (bukan object ber-key seperti "cls4"). Kalau tidak, StarUML tidak bisa membaca dan diagram harus disusun ulang manual. Builder yang memakai classViewData: buildModelsMdj (00), buildErdMdj (02).

## Node activity = 3 elemen [type, name, actor]
Data flow activity (rawActivityManual + flow turunan dari narrative) memakai array 3-elemen per node: [type, name, actor] dengan actor salah satu Pengguna/Sistem/Admin/Guru/Siswa (null untuk initial/final). Actor ini dipakai untuk swimlane: buildActivityXmi emits uml:ActivityPartition + <node xmi:idref>, buildActivityMdj membuat UMLActivityPartition + UMLPartitionView (band per aktor). Jangan kembali ke 2 elemen, dan jalankan `php generate-uml.php scan` setelah mengubah data agar JSON ter-update.

## Partition swimlane StarUML = UMLSwimlaneView + field groups
Di MDJ StarUML, activity partition: model UMLActivityPartition ditaruh di properti `groups` UMLActivity (BUKAN `partitions`), child nodes sebagai refs di field `nodes` partition. View partition = `UMLSwimlaneView` (BUKAN UMLPartitionView yang tidak ada di StarUML!) dengan `isVertical: true` dan nameLabel ref ke LabelView yang DIEMBED di subViews view. Kalau tipe view salah, StarUML gagal render dan edge/control flow bisa tidak tampil.
