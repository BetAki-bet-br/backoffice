import BasePage from './BasePage';

export default class ProvidersPage extends BasePage {
  constructor() {
    super('/providers');
  }

  getSearchInput() {
    return cy.get('#q');
  }

  getStatusFilter() {
    return cy.get('#status');
  }

  getVerticalFilter() {
    return cy.get('#vertical');
  }

  searchByQuery(query) {
    this.getSearchInput().clear().type(query);
    cy.get('#btnSearch').click();
    return this;
  }

  filterByVertical(vertical) {
    this.getVerticalFilter().select(vertical);
    cy.get('#btnSearch').click();
    return this;
  }

  filterByStatus(status) {
    this.getStatusFilter().select(status);
    cy.get('#btnSearch').click();
    return this;
  }

  clearFilters() {
    cy.get('#btnClear').click();
    return this;
  }

  clickSync() {
    cy.get('#btnSync').click();
    return this;
  }

  clickReload() {
    cy.get('#btnReload').click();
    return this;
  }

  // --- Modal ---

  getModal() {
    return cy.get('#editModal');
  }

  getModalTitle() {
    return cy.get('#editTitle');
  }

  fillName(name) {
    cy.get('#f_name').clear().type(name);
    return this;
  }

  selectStatus(status) {
    cy.get('#f_status').select(status);
    return this;
  }

  checkVertical(vertical) {
    cy.get(`input[type="checkbox"][value="${vertical}"]`).check();
    return this;
  }

  clickSave() {
    cy.get('#btnSave').click();
    return this;
  }

  clickDeactivateSlots() {
    cy.get('#btnDeactivateSlots').click();
    return this;
  }

  clickActivateSlots() {
    cy.get('#btnActivateSlots').click();
    return this;
  }

  // --- Row Actions ---

  clickEditOnRow(id) {
    cy.get(`button[data-action="edit"][data-id="${id}"]`).click();
    return this;
  }

  clickViewGamesOnRow(id) {
    cy.get(`button[data-action="games"][data-id="${id}"]`).click();
    return this;
  }

  // --- Pagination ---

  clickNext() {
    cy.get('#nextBtn').click();
    return this;
  }

  clickPrev() {
    cy.get('#prevBtn').click();
    return this;
  }

  getPaginationInfo() {
    return cy.get('#paginationInfo');
  }
}
