import BasePage from './BasePage';

export default class TopWinnersPage extends BasePage {
  constructor() {
    super('/top-winners');
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

  selectVertical(vertical) {
    cy.get('#f_vertical').select(vertical);
    return this;
  }

  selectStatus(status) {
    cy.get('#f_status').select(status);
    return this;
  }

  fillPeriodStart(date) {
    cy.get('#f_period_start').clear().type(date);
    return this;
  }

  fillPeriodEnd(date) {
    cy.get('#f_period_end').clear().type(date);
    return this;
  }

  fillTopN(n) {
    cy.get('#f_top_n').clear().type(String(n));
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

  clickWinnersOnRow(id) {
    cy.get(`button[data-action="winners"][data-id="${id}"]`).click();
    return this;
  }

  clickPublishOnRow(id) {
    cy.get(`button[data-action="publish"][data-id="${id}"]`).click();
    return this;
  }

  clickArchiveOnRow(id) {
    cy.get(`button[data-action="archive"][data-id="${id}"]`).click();
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
