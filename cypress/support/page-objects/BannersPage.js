import BasePage from './BasePage';

export default class BannersPage extends BasePage {
  constructor() {
    super('/banners');
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

  getCountriesFilter() {
    return cy.get('#countries');
  }

  searchBySlug(slug) {
    this.getSearchInput().clear().type(slug);
    cy.get('#btnSearch').click();
    return this;
  }

  filterByStatus(status) {
    this.getStatusFilter().select(status);
    cy.get('#btnSearch').click();
    return this;
  }

  filterByVertical(vertical) {
    this.getVerticalFilter().select(vertical);
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

  fillSlug(slug) {
    cy.get('#f_slug').clear().type(slug);
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

  fillCountries(countries) {
    cy.get('#f_countries').clear().type(countries);
    return this;
  }

  fillLinkUrl(url) {
    cy.get('#f_link_url').clear().type(url);
    return this;
  }

  fillPublishAt(datetime) {
    cy.get('#f_publish_at').clear().type(datetime);
    return this;
  }

  fillExpireAt(datetime) {
    cy.get('#f_expire_at').clear().type(datetime);
    return this;
  }

  fillUtmSource(value) {
    cy.get('#f_utm_source').clear().type(value);
    return this;
  }

  fillUtmMedium(value) {
    cy.get('#f_utm_medium').clear().type(value);
    return this;
  }

  fillUtmCampaign(value) {
    cy.get('#f_utm_campaign').clear().type(value);
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

  clickPublishOnRow(id) {
    cy.get(`button[data-action="publish"][data-id="${id}"]`).click();
    return this;
  }

  clickCopyLinkOnRow(id) {
    cy.get(`button[data-action="copy"][data-id="${id}"]`).click();
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

  // --- Translations ---

  clickAddTranslation() {
    cy.get('#addTranslation').click();
    return this;
  }
}
