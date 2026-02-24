import BasePage from './BasePage';

export default class CategoriesPage extends BasePage {
  constructor() {
    super('/categories');
  }

  // --- Filters ---

  getSearchInput() {
    return cy.get('#q');
  }

  getStatusFilter() {
    return cy.get('#status');
  }

  getVerticalFilter() {
    return cy.get('#vertical');
  }

  getTypeFilter() {
    return cy.get('#tipo');
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

  filterByType(type) {
    this.getTypeFilter().select(type);
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

  // --- CRUD ---

  clickNew() {
    cy.get('#btnNew').click();
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

  // --- Modal Form ---

  getModal() {
    return cy.get('#editModal');
  }

  getModalTitle() {
    return cy.get('#editTitle');
  }

  fillNome(nome) {
    cy.get('#f_nome').clear().type(nome);
    return this;
  }

  fillSlug(slug) {
    cy.get('#f_slug').clear().type(slug);
    return this;
  }

  selectType(type) {
    cy.get('#f_tipo').select(type);
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

  getSaveError() {
    return cy.get('#saveError');
  }

  // --- Slots Management ---

  clickSlotsOnRow(id) {
    cy.get(`button[data-action="slots"][data-id="${id}"]`).click();
    return this;
  }

  // --- Row Actions ---

  clickEditOnRow(id) {
    cy.get(`button[data-action="edit"][data-id="${id}"]`).click();
    return this;
  }

  clickDeleteOnRow(id) {
    cy.get(`button[data-action="delete"][data-id="${id}"]`).click();
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
