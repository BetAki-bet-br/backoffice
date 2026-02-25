import BasePage from './BasePage';

export default class FootersPage extends BasePage {
  constructor() {
    super('/footers');
  }

  getSearchInput() {
    return cy.get('#q');
  }

  getStatusFilter() {
    return cy.get('#status');
  }

  getCountryFilter() {
    return cy.get('#country');
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

  filterByCountry(country) {
    this.getCountryFilter().clear().type(country);
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

  fillKey(key) {
    cy.get('#f_key').clear().type(key);
    return this;
  }

  selectStatus(status) {
    cy.get('#f_status').select(status);
    return this;
  }

  fillCountry(country) {
    cy.get('#f_country').clear().type(country);
    return this;
  }

  fillBrand(brand) {
    cy.get('#f_brand').clear().type(brand);
    return this;
  }

  fillPublishAt(datetime) {
    cy.get('#f_publish_at').clear().type(datetime);
    return this;
  }

  clickAddTranslation() {
    cy.get('#addTranslation').click();
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

  clickLinksOnRow(id) {
    cy.get(`button[data-action="links"][data-id="${id}"]`).click();
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
