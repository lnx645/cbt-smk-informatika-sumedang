<script>
    import {
        Badge,
        Button,
        ButtonGroup,
        ListGroup,
        ListGroupItem,
        TabContent,
        TabPane,
    } from '@sveltestrap/sveltestrap';
</script>

<div class="d-flex flex-column gap-0 lh-1">
    <h4 class="fw-bold fs-5">Kelas</h4>
    <p class="text-xs">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Ea, unde.
    </p>
</div>
<TabContent>
    <TabPane tab="Feeds" tabId="feeds" active>Feeds</TabPane>
    <TabPane tab="Tugas" tabId="tugas" active>
        <div class="mt-3">
            <Button color="primary" size="sm">Buat Tugas</Button>
            <Button color="primary" size="sm">Periksa Tugas</Button>
            <ListGroup class="mt-3" color="warning">
                {#each [1, 2, 3] as e}
                    <ListGroupItem color="white" class="bg-white">
                        <div class="fw-bold fs-4 d-flex align-items-center">
                            Tugas 1 - Topologi Jaringan <Badge
                                color="danger"
                                class="text-xs ms-2 fw-light"
                                >Belum Terbit</Badge
                            >
                        </div>
                        <div class="text-sm">
                            Menggambar skema jaringan komputer sebuah gedung
                            sekolah atau kantor menggunakan aplikasi Cisco
                            Packet Tracer.
                        </div>
                        <div class="text-xs mt-2 text-muted fw-semibold">
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi text-danger bi-file-earmark-pdf"
                                ></i>
                                <span>(5) Lampiran</span>
                            </div>
                            <div class="d-flex align-items-center gap-1">
                                <i class="bi text-muted bi-calendar-heart"></i>
                                <span
                                    >29 Oktober 2023 18:00 / 29 Oktober 2023
                                    17:00</span
                                >
                            </div>
                        </div>
                        <div class="mt-2">
                            <ButtonGroup size="sm">
                                <Button color="info">Terbitkan</Button>
                                <Button color="info">Periksa</Button>
                            </ButtonGroup>
                        </div>
                    </ListGroupItem>
                {/each}
            </ListGroup>
        </div>
    </TabPane>
    <TabPane tab="Materi" tabId="materi">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Corporis
        debitis quisquam iure doloribus dolores sunt, veritatis quos cupiditate
        ipsam nam vero, fugit perspiciatis maiores obcaecati id praesentium,
        natus at tenetur?
    </TabPane>
    <TabPane tab="Quiz" tabId="QUIZ">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Corporis
        debitis quisquam iure doloribus dolores sunt, veritatis quos cupiditate
        ipsam nam vero, fugit perspiciatis maiores obcaecati id praesentium,
        natus at tenetur?
    </TabPane>
    <TabPane tab="Forum Diskusi" tabId="Forum & Diskusi Kelas">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Corporis
        debitis quisquam iure doloribus dolores sunt, veritatis quos cupiditate
        ipsam nam vero, fugit perspiciatis maiores obcaecati id praesentium,
        natus at tenetur?
    </TabPane>

    <TabPane tab="Siswa" tabId="Siswa">
        Lorem ipsum dolor sit amet consectetur adipisicing elit. Corporis
        debitis quisquam iure doloribus dolores sunt, veritatis quos cupiditate
        ipsam nam vero, fugit perspiciatis maiores obcaecati id praesentium,
        natus at tenetur?
    </TabPane>
</TabContent>
