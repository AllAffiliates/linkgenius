import { Spinner, Button, Popover } from "@wordpress/components";
import { Component, Fragment } from '@wordpress/element';
import{ __ } from "@wordpress/i18n";
import { URLInput } from "@wordpress/block-editor";
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

export class LinkSelector extends Component {
  constructor() {
    super(...arguments);
    this.state = {
      search: "",
      suggestions: [], // To store the list of suggestions
      selectedLink: null,
      isLoading: this.props.link_id !== undefined && this.props.link_id != "",
      isEditing: this.props.link_id === undefined || this.props.link_id == ""
    }
    this.handleInputChange = this.handleInputChange.bind(this);
    this.handleSelectSuggestion = this.handleSelectSuggestion.bind(this);
    this.handleBreak = this.handleBreak.bind(this)
    if(this.props.link_id !== undefined && this.props.link_id != "") {
      getLink(this.props.link_id).then(link => {
        this.setState({selectedLink : link, isLoading: false})
        if(this.props.onLinkLoaded !== undefined) {
          this.props.onLinkLoaded(link)
        }
      })
    }
  }

  handleInputChange(newText) {
    // Perform the search and update the suggestions list
    this.setState({loading: true,  search:newText});
    performSearch(newText).then(searchResults => {
        if(this.state.search !== newText) {
            // Canceled
            return
        }
        this.setState({ suggestions: searchResults, loading: false });
    })
  }

  handleSelectSuggestion(selectedLink) {
    // Pass the selected link data to the parent component
    this.setState({
        selectedLink: selectedLink,
        isEditing: false,
        isLoading: false
    });
    this.props.onChange(selectedLink.id, selectedLink); // Pass the ID and other relevant data
  }

  handleBreak() {
    this.setState({
      isEditing: false,
      isLoading: true
    })
    getLinkByUrl(this.state.search).then(
      link => {
        if(link !== null) {
          this.handleSelectSuggestion(link)
        }
        else {
          this.setState({isLoading: false})
        }
      })
  }

  render() {
    return (
      <div>
          <div className="linkgenius-preview editor-format-toolbar__link-container-content block-editor-format-toolbar__link-container-content">
            {this.state.selectedLink !== null ? (
                <a href={this.state.selectedLink.url} target="_blank">{this.props.link_text ?? this.state.selectedLink.url}</a>
            ) : (this.props.link_id === undefined && __('No link selected', 'linkgenius'))}
            {(this.state.isLoading) && (<Spinner></Spinner>)}
            <Button icon="edit" label={__('Edit', 'linkgenius')} onClick={() => this.setState({
                  isEditing: true
                })} />
          </div>
        
        {
          this.state.isEditing &&
          <Popover 
            focusOnMount={false}
            autoFocus={false}
            onClose={() => {
              this.setState({ isEditing: false})
            }}
            placement={this.props.placement ?? "bottom-start"}
          >
            <div className="block-editor-url-popover__row" onKeyDown={(ev) => {
                  if(ev.key === 'Enter') {
                    this.handleBreak()
                  }
                }}>
              <URLInput value={this.state.search} onChange={this.handleInputChange} disableSuggestions={true} autoFocus={true} />
              <Button icon="editor-break" label={ __( 'Insert LinkGenius Link' ) } onClick={this.handleBreak} />
            </div>
            {this.state.suggestions.length > 0 && ( 
              <Fragment> 
                <div
                className="editor-url-input__suggestions block-editor-url-input__suggestions"
                role="listbox"
                >
                { this.state.suggestions.map( ( link, index ) => (
                    <button
                    key={ link.id }
                    tabIndex={-1}
                    className={ 'editor-url-input__suggestion block-editor-url-input__suggestion'
                        +  link.id === this.state.selectedSuggestionId ? ' is-selected' : ""
                    }
                    onClick={ () => this.handleSelectSuggestion(link) }
                    >
                    <span className="linkgenius_link_title">{ link.title }</span><br/>
                    <span className="linkgenius_link_url">{ link.url }</span><br />
                    <span className="linkgenius_link_target">{ link.target_url }</span><br />
                    </button>
                ) ) }
                </div>
              </Fragment>
            )}
          </Popover>
        }
      </div>
    );
  }
}

/**
 * Helper function to perform the search and return the results
 * @param {string} query
 */
async function performSearch(query) {
    const response = await apiFetch( {
        url: addQueryArgs( ajaxurl, {
            action: 'search_linkgenius_links',
            keyword: query
        })
    })
    if(response.status === 'success') {
        const links = response.links
        return links
    }
    else {
        return []
    }

}

/**
 * @param {number} id
 */
export async function getLink(id) {
  const response = await apiFetch({
    url: addQueryArgs( ajaxurl, {
      action: 'get_linkgenius_link',
      linkgenius_id: id
    })
  })
  if(response.status === 'success') {
    const link = response.link
    return link
  }
  else {
    return null
  }
}

async function getLinkByUrl(url) {
  const response = await apiFetch({
    url: addQueryArgs( ajaxurl, {
      action: 'get_linkgenius_link',
      linkgenius_url: url
    })
  })
  if(response.status === 'success') {
    const link = response.link
    return link
  }
  else {
    return null
  }
}