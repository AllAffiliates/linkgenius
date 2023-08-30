import { __ } from "@wordpress/i18n"
import { useState, Fragment } from "@wordpress/element";
import { TextareaControl, ComboboxControl, RadioControl, Spinner,  ToolbarGroup, ToolbarButton } from "@wordpress/components";
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { BlockControls } from "@wordpress/block-editor";

function LinkGeniusTaxonomySelector(props) {
    if(props === undefined)
        return;
    const {attributes, setAttributes, taxonomy } = props;
    const [taxonomyItems, setTaxonomyItems] = useState(null);
    const [previousPreviewMode, setPreviousPreviewMode] = useState(null);
    const [preview, setPreview] = useState(null);
    const [previousAttributes, setPreviousAttributes] = useState(null);
    const [isPreviewMode, setPreviewMode] = useState(attributes.item_slug !== undefined && attributes.item_slug != "");

    // set for next cycle
    if(previousPreviewMode !== isPreviewMode) {
        setPreviousPreviewMode(isPreviewMode);
    }
    // compare for this cycle
    if(preview === null || (previousPreviewMode !== isPreviewMode  && isPreviewMode === true)) {
        if(previousAttributes === null || attributes.item_slug !== previousAttributes.item_slug || attributes.sort !== previousAttributes.sort || attributes.template !== previousAttributes.template) {
            setPreview("");
            getPreview(taxonomy, attributes.item_slug, attributes.sort, attributes.template).then((preview) => {
                setPreview(preview);
            })
            setPreviousAttributes(attributes);
        }
    }


    if(taxonomyItems === null) {
        if(!fetchers[taxonomy]) {
            fetchers[taxonomy] = new TaxonomyFetcher(taxonomy);
        }
        fetchers[taxonomy].subscribers.push(setTaxonomyItems);
        fetchers[taxonomy].promise.then(() => {
            setTaxonomyItems(fetchers[taxonomy].options)
        });
    }
    return (<Fragment>
        <BlockControls>
            <ToolbarGroup label={ __( 'LinkGenius List Settings' ) }>
				<ToolbarButton
					label={ isPreviewMode ? __( 'Edit LinkGenius List', 'linkgenius' ) : __( 'Preview LinkGenius List', 'linkgenius' ) }
					icon={isPreviewMode ? "edit" : "visibility"}
					onClick={() => setPreviewMode(!isPreviewMode) }
				/>
			</ToolbarGroup>
        </BlockControls>
        { isPreviewMode ? 
            (
                <div>
                    {preview === "" ? <div className="linkgenius-taxonomy-select"><Spinner></Spinner></div> : <div className="linkgenius-list-preview" dangerouslySetInnerHTML={{__html: preview}}></div>}
                </div>
            ) : (
                <div className="linkgenius-taxonomy-select">
                    <h2>{taxonomy == 'category'? __('LinkGenius Category List', 'linkgenius') : __('LinkGenius Tag List', 'linkgenius')}</h2>
                    <div>
                        {taxonomy == 'category'? __('Category', 'linkgenius') : __('Tag', 'linkgenius')}:
                        <ComboboxControl
                            value={attributes.item_slug??""}
                            options={taxonomyItems??[]}
                            onChange={(newValue) => setAttributes({ item_slug: newValue })}
                        />
                    </div>
                    <div className="linkgenius-list-sort">
                        {__('Sort By', 'linkgenius')}: 
                        <RadioControl options={[
                            { label: __('Order', 'linkgenius'), value: 'order' },
                            { label: __('Title', 'linkgenius'), value: 'title' },
                        ]} selected={attributes.sort??'order'} onChange={(newValue) => setAttributes({ sort: newValue })} />
                    </div>
                    <div>
                        {__('Template or seperator', 'linkgenius')}:
                        <div className="linkgenius-template-desc">{__('If {links}{link}{/links} is not found value is treated as seperator. For example, Set to ", " to make links comma seperated', 'linkgenius')}</div>
                        <TextareaControl rows={5} onChange={(newtext) => setAttributes({ template: newtext })}>{attributes.template}</TextareaControl>
                    </div>
                </div>
            )}
    </Fragment>)
} 
export default LinkGeniusTaxonomySelector;

const fetchers = [];
class TaxonomyFetcher {
    subscribers = [];
    options = [];
    promise = null;
    constructor(taxonomy) 
    {
        const fetchData = async () => {
            const per_page = 100;
            let total = [];
            for(let page = 1; page < 100; page++) {
                const url = wpApiSettings.root+"wp/v2/linkgenius_"+taxonomy+(wpApiSettings.root.includes("?")?"&":"?")+"per_page="+per_page+"&page="+page
                const response = await fetch(url)
                if(response.status !== 200)
                    break;
                const data = await response.json()
                total = total.concat(Array.isArray(data) ? data.map((o) => { return { value: o.slug, label: o.name}}) : []);
                this.options = total
                this.subscribers.forEach((subscriber) => {
                    subscriber(this.options);
                })
                if(data.length < per_page)
                    break;
            }
        };
        this.promise = fetchData() 
    }
}
async function getPreview(taxonomy, item_slug, sort, template) {
    const response = await apiFetch({
        url: addQueryArgs( ajaxurl, {
            action: 'preview_linkgenius_taxonomy',
            taxonomy: taxonomy,
            item_slug: item_slug,
            sort: sort,
            template: template
        })
      })
      if(response.success) {
        const preview = response.data
        return preview !== '' ? preview : __('No LinkGenius links found', 'linkgenius');
      }
      else {
        return null
      }
}