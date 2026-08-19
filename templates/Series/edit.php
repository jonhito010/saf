<?php
/**
 * @var \App\View\AppView $this
 * @var \App\Model\Entity\Series $series
 */
?>
 <!-- Page Title Start -->
 <div class="flex items-center md:justify-between flex-wrap gap-2 mb-5">
                    <h4 class="text-default-900 text-lg font-semibold">Series</h4>

                    <div class="md:flex hidden items-center gap-3 text-sm font-semibold">


                        <a href="javascript:void(0)" class="text-sm font-medium text-default-700" aria-current="page">Nueva</a>
                    </div>
                </div>
                <!-- Page Title End -->

                <div class="grid lg:grid-cols-1 grid-cols-1 gap-12">
                    <div class="card">
                        <div class="p-6">
                            <h4 class="card-title mb-4">Nueva Serie</h4>
      
                                <?= $this->Form->create($series) ?>
                                    <div class="grid grid-cols-1 md:grid-cols-2  gap-2">
                                        <div>
                                            <label for="serie" class="text-default-800 text-sm font-medium inline-block mb-2">Serie</label>
                                            <input type="text" required class="form-input" value="<?= h($series->serie) ?>" id="serie" name="serie" placeholder="A" required>
                                          </div>
                                        <div>
                                            <label for="folioIncial" class="text-default-800 text-sm font-medium inline-block mb-2">Folio Inicial</label>
                                            <input type="number" required class="form-input" value="<?= h($series->folioIncial) ?>" id="folioIncial" name="folioIncial" placeholder="1" inputmode="numeric" pattern="\\d+" required>
                                        </div>

                                        <div class="lg:col-span-2">
                                            <label for="folioActual" class="text-default-800 text-sm font-medium inline-block mb-2">Folio Actual</label>
                                            <input type="number" required class="form-input" value="<?= h($series->folioActual) ?>" id="folioActual" name="folioActual" placeholder="1" inputmode="numeric" pattern="\\d+" required>
                                         </div>
                                         <div>
                                            <label for="activo" class="text-default-800 text-sm font-medium inline-block mb-2">Activo</label>
                                            <select name="activo" id="activo" class="form-select">
                                                <option value="1" <?= $series->activo == 1 ? 'selected' : '' ?>>Si</option>
                                                <option value="0" <?= $series->activo == 0 ? 'selected' : '' ?>>No</option>
                                            </select>
                                         </div>
                                    </div>
                                    <br>
                                    <center>
                                    <a href="series/index" class="btn bg-secondary text-white"><i class="fa fa-arrow-left"></i> Cancelar</a>
                                    <button type="submit" class="btn bg-primary text-white"><i class="fa fa-save"></i> Guardar</button>
                                        
                                </center>
                                    <?= $this->Form->end() ?>

                              

                            </div>
                        </div> 
                    </div> <!-- end col -->
                </div>
