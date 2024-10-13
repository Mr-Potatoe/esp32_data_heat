<div class="d-flex justify-content-between align-items-center mt-4">
               
               <div>
                   <!-- Previous Page Link -->
                   <?php if ($page > 1): ?>
                       <a href="?page=<?= $page - 1 ?>&filter=<?= $filterType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-primary">
                           <i class="bi bi-chevron-left"></i> Previous
                       </a>
                   <?php else: ?>
                       <button class="btn btn-outline-secondary" disabled>
                           <i class="bi bi-chevron-left"></i> Previous
                       </button>
                   <?php endif; ?>

               </div>

                   <div>
                   <span>Page <?= $page ?> of <?= $totalPages ?></span>
               </div>
               <div>
                   <!-- Next Page Link -->
                   <?php if ($page < $totalPages): ?>
                       <a href="?page=<?= $page + 1 ?>&filter=<?= $filterType ?>&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>" class="btn btn-outline-primary">
                           Next <i class="bi bi-chevron-right"></i>
                       </a>
                   <?php else: ?>
                       <button class="btn btn-outline-secondary" disabled>
                           Next <i class="bi bi-chevron-right"></i>
                       </button>
                   <?php endif; ?>
               </div>
           </div>