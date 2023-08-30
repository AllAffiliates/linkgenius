import { LinkSelector, getLink } from "./linkgenius-link-selector";
import { linkgenius_icon } from "./linkgenius-icon";
import { __ } from "@wordpress/i18n"
import { TextControl, Spinner, ToolbarGroup, ToolbarButton } from "@wordpress/components"
import { useState } from "@wordpress/element";
const { registerBlockType } = wp.blocks;
import { BlockControls } from "@wordpress/block-editor";
import LinkGeniusTaxonomySelector from "./linkgenius-taxonomy-selector";

registerBlockType('linkgenius/linkblock', {
	title: 'LinkGenius Link',
	category: 'linkgenius',
	icon: linkgenius_icon,
	attributes: {
		text: {
			type: "string",
		},
		linkgenius_id: {
			type: "string",
		}
	},
	description: 'Display an All Affiliate Link ',
	keywords: ['LinkGenius', 'affiliate', 'link'],
	edit: (props) => {
		if(props === undefined)
			return;
		const {attributes, setAttributes } = props;
		const [link, setLink] = useState(null);
		const [isPreviewMode, setPreviewMode] = useState(attributes.linkgenius_id !== undefined);
		const [loading, setLoading] = useState(attributes.linkgenius_id !== undefined);
		
		let unselected = "";
		if(isPreviewMode) {
			if(loading) {
				unselected = (<div className="linkgenius-link-preview"><Spinner></Spinner></div>)
			}
			else {
				if(link === null) {
					unselected = (<div className="linkgenius-link-preview">{__("No LinkGenius Link Selected", 'linkgenius')}</div>)
				}
				else {
					unselected = (<div className="linkgenius-link-preview"><a href={link.url}>{attributes.text}</a></div>)
				}
			}
		}
		return (
			<div>
				<BlockControls>
					<ToolbarGroup label={ __( 'LinkGenius Link Settings' ) }>
						<ToolbarButton
							label={ isPreviewMode ? __( 'Edit LinkGenius Link', 'linkgenius' ) : __( 'Preview LinkGenius Link', 'linkgenius' ) }
							icon={isPreviewMode ? "edit" : "visibility"}
							onClick={() => setPreviewMode(!isPreviewMode) }
						/>
					</ToolbarGroup>
				</BlockControls>
				<div className={!isPreviewMode ? "linkgenius-link-wrapper" : "linkgenius-link-wrapper-hidden"}>
					<h2>{__("LinkGenius Link", 'linkgenius')}</h2>
					{__('Text', 'linkgenius')}: <TextControl 
						value={attributes.text}
						onChange={(newtext) => setAttributes({ text: newtext })}
					/>
					{__('Link', 'linkgenius')}:
					<LinkSelector 
						link_id={attributes.linkgenius_id}
						onChange={(id, link) => {
							setAttributes({linkgenius_id: id+""});
							setLink(link);
						}}
						onLinkLoaded={link => {
							setLink(link)
							setLoading(false)
						}}
					/>
				</div>
				{unselected}
			</div>	
		);
	},
	save: ({attributes}) => {
        const shortcodeString = '[linkgenius-link id="'+(attributes.linkgenius_id??'')+'"]'+attributes.text+'[/linkgenius-link]'
		return <wp.element.RawHTML>{shortcodeString}</wp.element.RawHTML>
	}
});

registerBlockType('linkgenius/categoryblock', {
	title: 'LinkGenius Category List',
	category: 'linkgenius',
	icon: linkgenius_icon,
	attributes: {
		item_slug: {
			type: "string",
		},
		template: {
			type: "string",
			default: `<ul>
	{links}
		<li>{link}</li>
	{/links}
</ul>`
		},
		sort: {
			type: "string",
			default: "order"
		}
	},
	description: 'Display all links of an All Affiliate Link Category ',
	keywords: ['LinkGenius', 'affiliate', 'link', 'category'],
	edit: (props) => {
		return <LinkGeniusTaxonomySelector {...props} taxonomy="category" />
	},
	save: ({attributes}) => {
		const shortcodeString = '[linkgenius-list category="'+(attributes.item_slug??'')+'"]'+(attributes.template??'')+'[/linkgenius-list]';
		return <wp.element.RawHTML>{shortcodeString}</wp.element.RawHTML>
	}
});

registerBlockType('linkgenius/tagblock', {
	title: 'LinkGenius Tag List',
	category: 'linkgenius',
	icon: linkgenius_icon,
	attributes: {
		item_slug: {
			type: "string",
		},
		template: {
			type: "string",
			default: `<ul>
	{links}
		<li>{link}</li>
	{/links}
</ul>`
		},
		sort: {
			type: "string",
			default: "order"
		}
	},
	description: 'Display all links of an LinkGenius Tag ',
	keywords: ['LinkGenius', 'affiliate', 'link', 'tag'],
	edit: (props) => {
		return <LinkGeniusTaxonomySelector {...props} taxonomy="tag" />
	}
	,
	save: ({attributes}) => {
		const shortcodeString = '[linkgenius-list tag="'+(attributes.item_slug??'')+'"]'+(attributes.template??'')+'[/linkgenius-list]';
		return <wp.element.RawHTML>{shortcodeString}</wp.element.RawHTML>
	}
});