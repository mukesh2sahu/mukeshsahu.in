<?php
/**
 * Admin Panel Footer Component
 * MukeshSahu Platform
 */

declare(strict_types=1);
?>
        </main>
        
        <footer class="bg-white border-top py-3 px-4 text-center text-muted small">
            &copy; <?= date('Y') ?> <strong><?= e(get_setting('site_name', 'MukeshSahu')) ?></strong> Admin Control Console. Built with Core PHP & MySQL.
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery & DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    $(document).ready(function() {
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                responsive: true,
                pageLength: 10,
                order: [[0, 'desc']],
                language: {
                    search: "_INPUT_",
                    searchPlaceholder: "Quick search records..."
                }
            });
        }
    });
</script>
</body>
</html>
