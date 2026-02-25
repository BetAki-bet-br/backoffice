import BasePage from './BasePage';

export default class TopListsPage extends BasePage {
  constructor() {
    super('/top-lists');
  }

  getSearchInput() {
    return cy.get('#q');
  }

  getStatusFilter() {
    return cy.get('#status');
  }

  searchByQuery(query) {
    this.getSearchInput().clear().type(query);
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

  clickNew() {
    cy.get('#btnNew').click();
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

  fillTitle(title) {
    cy.get('#f_title').clear().type(title);
    return this;
  }

  fillSlug(slug) {
    cy.get('#f_slug').clear().type(slug);
    return this;
  }

  selectStatus(status) {
    cy.get('#f_status').select(status);
    return this;
  }

  selectVertical(vertical) {
    cy.get('#f_vertical').select(vertical);
    return this;
  }

  selectType(type) {
    cy.get('#f_type').select(type);
    return this;
  }

  fillValidFrom(date) {
    cy.get('#f_valid_from').clear().type(date);
    return this;
  }

  fillValidUntil(date) {
    cy.get('#f_valid_until').clear().type(date);
    return this;
  }

  clickSave() {
    cy.get('#btnSave').click();
    return this;
  }

  getSaveError() {
    return cy.get('#saveError');
  }

  // --- Row Actions ---

  clickEditOnRow(id) {
    cy.get(`button[data-action="edit"][data-id="${id}"]`).click();
    return this;
  }

  clickSlotsOnRow(id) {
    cy.get(`button[data-action="slots"][data-id="${id}"]`).click();
    return this;
  }

  clickPublishOnRow(id) {
    cy.get(`button[data-action="publish"][data-id="${id}"]`).click();
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
