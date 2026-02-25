import BasePage from './BasePage';

export default class UsersPage extends BasePage {
  constructor() {
    super('/users');
  }

  // --- Filters ---

  getSearchInput() {
    return cy.get('#q');
  }

  searchByQuery(query) {
    this.getSearchInput().clear().type(query);
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

  fillName(name) {
    cy.get('#f_name').clear().type(name);
    return this;
  }

  fillEmail(email) {
    cy.get('#f_email').clear().type(email);
    return this;
  }

  fillPassword(password) {
    cy.get('#f_password').clear().type(password);
    return this;
  }

  selectStatus(status) {
    cy.get('#f_status').select(status);
    return this;
  }

  checkRole(role) {
    cy.get('#rolesWrap').contains('label', role).find('input[type="checkbox"]').check();
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
