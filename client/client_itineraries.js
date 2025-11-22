let selectedDestination; 

// Show the edit modal
function displayEditModal(destination) {
  selectedDestination = destination;
  document.getElementById('editMessage').textContent = `Edit itinerary for: ${destination}`;
  document.getElementById('editInput').value = destination; 
  document.getElementById('editModal').classList.remove('modal-hidden'); 
}

// Edit confirmation
function applyEdit() {
  const newDestination = document.getElementById('editInput').value;
  const row = document.querySelector(`tr:has(td:contains(${selectedDestination}))`);
  if (row) {
    row.cells[0].innerText = newDestination; 
  }
  closeEditModal(); 
}

// Delete confirmation modal
function displayDeleteModal(destination) {
  selectedDestination = destination; 
  document.getElementById('deleteMessage').textContent = `Are you sure you want to delete the itinerary for: ${destination}?`;
  document.getElementById('deleteModal').classList.remove('modal-hidden'); // Show the modal
}

// Handle the delete confirmation
function applyDelete() {
  const row = document.querySelector(`tr:has(td:contains(${selectedDestination}))`);
  if (row) {
    row.remove(); 
  }
  closeDeleteModal(); 
}

// close the edit modal
function closeEditModal() {
  document.getElementById('editModal').classList.add('modal-hidden');
}

// Close the delete modal
function closeDeleteModal() {
  document.getElementById('deleteModal').classList.add('modal-hidden');
}

// Add event listeners to the icons
document.addEventListener("DOMContentLoaded", function () {
  const editIcons = document.querySelectorAll('.action-icons img[alt="Edit"]');
  const deleteIcons = document.querySelectorAll('.action-icons img[alt="Delete"]');

  // Event listeners for edit icons
  editIcons.forEach(icon => {
    icon.addEventListener('click', function () {
      const destination = this.closest('tr').cells[0].innerText; 
      displayEditModal(destination); 
    });
  });

  // Event listeners for delete icons
  deleteIcons.forEach(icon => {
    icon.addEventListener('click', function () {
      const destination = this.closest('tr').cells[0].innerText; 
      displayDeleteModal(destination);
    });
  });

  // Confirm edit button
  document.getElementById('confirmEdit').addEventListener('click', applyEdit);

  // Cancel edit button
  document.getElementById('cancelEdit').addEventListener('click', closeEditModal);

  // Confirm delete button
  document.getElementById('confirmDelete').addEventListener('click', applyDelete);

  // Cancel delete button
  document.getElementById('cancelDelete').addEventListener('click', closeDeleteModal);
});