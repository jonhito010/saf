<?php
/**
 * @var \App\View\AppView $this
 * @var iterable<\App\Model\Entity\Series> $series
 */
?>
<div class="flex items-center md:justify-between flex-wrap gap-2 mb-5">
                    <h4 class="text-default-900 text-lg font-semibold">Series</h4>

                    <div class="md:flex  items-center gap-3 text-sm font-semibold">
                       
                        <a href="series/add" class="btn bg-primary text-white" ><i class="fa-solid fa-plus"></i> Nueva Serie</a>
                    </div>
</div>

<div class="grid lg:grid-cols-1 gap-12 mt-8">
<div class="card overflow-hidden">
                        <div class="p-4">
                            <div class="overflow-x-auto">
                                <div class="min-w-full inline-block align-middle">
                                    <div class="border rounded-lg overflow-hidden">
                                        <table id="dataTable" class="min-w-full divide-y divide-default-200">
                                            <thead class="bg-default-50">
                                                <tr>
                                                    
                                                    <th scope="col"
                                                        class="px-6 py-3 text-start text-sm text-default-500">
                                                        Serie</th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-start text-sm text-default-500">
                                                        Folio Inicial
                                                    </th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-start text-sm text-default-500">
                                                        Folio Actual
                                                    </th>
                                                    <th scope="col"
                                                        class="px-6 py-3 text-start text-sm text-default-500">
                                                        Activo
                                                    </th>
                                                    <th scope="col" class="px-6 py-3 text-end text-sm text-default-500">
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-default-200">


                                            <?php foreach ($series as $series): ?>
                                                <tr>
                                                   
                                                    <td
                                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-default-800">
                                                        <?= h($series->serie) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-default-800">
                                                        <?= $series->folioIncial === null ? '' : $this->Number->format($series->folioIncial) ?></td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-default-800">
                                                        <?= $series->folioActual === null ? '' : $this->Number->format($series->folioActual) ?></td>
                                                    
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-default-800">
                                                        <?= $series->activo == 1 ? 'Si' : 'No' ?>
                                                    </td>      
                                                    <td  class="px-6 py-4 text-center whitespace-nowrap text-end text-sm font-medium">
                                                        <button type="button" class="btn btn-primary" onclick="edit(<?= $series->id ?>)"    >    
                                                        <i class="fa fa-pencil" style="color:blue;" aria-hidden="true"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $series->id ?>)">
                                                        <i style="color:red;" class="fa fa-minus-circle" aria-hidden="true"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                                
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> <!-- end card -->
</div>          
<script>
    document.addEventListener('DOMContentLoaded', function() {
        initDataTable();
    });

    function edit(id) {
        window.location.href = 'series/edit/' + id;
    }

    function confirmDelete(id) {
        if (confirm('¿Estás seguro de querer eliminar esta serie?')) {
            window.location.href = 'series/delete/' + id;
        }
    }

    function initDataTable() {
        $('#dataTable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            order: [[0, 'asc']],
            lengthChange: false,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            searching: true,
            info: true,
            paging: true,
            pagingType: 'full_numbers'
        });
    }
</script>

 