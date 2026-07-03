@extends('frontend.layouts.app')
@section('content')
    <style>
        .home-slider {
            max-width: 100% !important;
        }
        .home-hero-stage{
            display: grid;
            grid-template-columns: 230px minmax(0, 1fr) 300px;
            gap: 12px;
            align-items: stretch;
            min-height: 408px;
        }
        .home-hero-menu,
        .home-hero-side,
        .home-hero-banner,
        .home-feature-strip-v2{
            background: #fff;
            border: 1px solid #f1f2f4;
            border-radius: 18px;
            box-shadow: 0 4px 18px rgba(15, 23, 42, 0.06);
        }
        .home-hero-menu{
            padding: 10px 0 8px;
            overflow: visible;
            position: relative;
            z-index: 6;
            height: 100%;
        }
        .home-hero-menu-head{
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            margin: 0 10px 8px;
            border-radius: 12px;
            background: linear-gradient(180deg, #fff4ed 0%, #fff8f4 100%);
            color: #ff6a00;
            font-size: 14px;
            font-weight: 800;
        }
        .home-hero-menu-list{
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .home-hero-menu-item{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 16px;
            color: #2d3748 !important;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none !important;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .home-hero-menu-item:hover{
            background: #fff7f1;
            color: #ff6a00 !important;
        }
        .home-hero-menu-item.is-view-all{
            color: #ff6a00 !important;
            font-weight: 700;
        }
        .home-hero-menu-copy{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            min-width: 0;
        }
        .home-hero-menu-icon{
            width: 18px;
            text-align: center;
            font-size: 17px;
            color: #4a5568;
            flex: 0 0 18px;
        }
        .home-hero-menu-item:hover .home-hero-menu-icon,
        .home-hero-menu-item.is-view-all .home-hero-menu-icon{
            color: #ff6a00;
        }
        .home-hero-menu-label{
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .home-hero-menu-arrow{
            color: #9aa3af;
            font-size: 16px;
            flex: 0 0 auto;
        }
        .home-hero-category-menu{
            background: transparent !important;
            border-top: 0 !important;
            width: auto !important;
            height: calc(100% - 54px);
            position: relative;
        }
        .home-hero-category-menu .categories{
            padding: 0 10px;
        }
        .home-hero-category-menu .category-nav-element{
            border: 0 !important;
            position: static;
        }
        .home-hero-category-menu .category-nav-element + .category-nav-element{
            margin-top: 2px;
        }
        .home-hero-category-menu .category-nav-element > a{
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 9px 12px !important;
            color: #2d3748 !important;
            font-size: 14px !important;
            font-weight: 500;
            text-decoration: none !important;
            position: relative;
            z-index: 10;
        }
        .home-hero-category-menu .category-nav-element > a > span{
            min-width: 0;
        }
        .home-hero-category-menu .category-nav-element:hover > a{
            background: var(--soft-secondary-base) !important;
            color: #2d3748 !important;
        }
        .home-hero-category-menu .category-nav-element > a .cat-image{
            width: 18px;
            height: 18px;
            object-fit: contain;
            opacity: 0.78 !important;
            margin-right: 10px !important;
            flex: 0 0 18px;
        }
        .home-hero-category-menu .category-nav-element:hover > a .cat-image{
            opacity: 1 !important;
        }
        .home-hero-category-menu .category-nav-element:hover > a .cat-name{
            margin-left: 5px;
        }
        .home-hero-category-menu .category-nav-element > a .cat-name{
            min-width: 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .home-hero-category-menu .category-nav-element > a::after{
            content: "\f105";
            font-family: "Line Awesome Free";
            font-weight: 900;
            color: #9aa3af;
            font-size: 14px;
            flex: 0 0 auto;
        }
        .home-hero-category-menu .category-nav-element:hover > a::after{
            color: #ff6a00;
        }
        .home-hero-category-menu .sub-cat-menu{
            left: calc(100% - 12px) !important;
            top: 0 !important;
            width: min(760px, calc(100vw - 420px)) !important;
            height: 100% !important;
            min-height: 408px;
            border: 1px solid #f1f2f4 !important;
            border-radius: 18px !important;
            box-shadow: 0 16px 40px rgba(15, 23, 42, 0.12) !important;
            background: #fff;
            z-index: -1;
            opacity: 0;
            transition: 0.5s;
        }
        .home-hero-category-menu .category-nav-element:hover .sub-cat-menu{
            z-index: 9;
            opacity: 1;
        }
        .home-hero-view-all{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 10px 12px;
            color: #ff6a00 !important;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none !important;
        }
        .home-hero-view-all:hover{
            background: var(--soft-secondary-base);
        }
        .home-hero-center{
            min-width: 0;
            height: 100%;
        }
        .home-hero-banner{
            position: relative;
            overflow: hidden;
            min-height: 408px;
            height: 100%;
        }
        .home-hero-banner .aiz-carousel,
        .home-hero-banner .slick-list,
        .home-hero-banner .slick-track,
        .home-hero-banner .carousel-box,
        .home-hero-banner .carousel-box > a{
            height: 100%;
        }
        .home-hero-slide{
            position: relative;
            display: block;
            width: 100%;
            height: 100%;
            min-height: 408px;
        }
        .home-hero-slide::before{
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(90deg, rgba(13, 25, 45, 0.88) 0%, rgba(13, 25, 45, 0.62) 34%, rgba(13, 25, 45, 0.18) 68%, rgba(13, 25, 45, 0.10) 100%),
                linear-gradient(180deg, rgba(255, 119, 0, 0.08) 0%, rgba(13, 25, 45, 0.18) 100%);
            z-index: 1;
        }
        .home-hero-slide img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-hero-overlay{
            position: absolute;
            inset: 0;
            z-index: 2;
            padding: 58px 48px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            max-width: 54%;
            color: #fff;
        }
        .home-hero-title{
            font-size: 34px;
            line-height: 1.08;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #fff;
            margin-bottom: 18px;
        }
        .home-hero-text{
            font-size: 16px;
            line-height: 1.7;
            color: rgba(255, 255, 255, 0.88);
            margin-bottom: 28px;
            max-width: 420px;
        }
        .home-hero-cta{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 156px;
            min-height: 40px;
            padding: 0 24px;
            border-radius: 8px;
            background: linear-gradient(180deg, #ff7d1a 0%, #ff6400 100%);
            color: #fff !important;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none !important;
            box-shadow: 0 10px 26px rgba(255, 106, 0, 0.26);
        }
        .home-hero-banner .slick-dots{
            bottom: 14px;
        }
        .home-hero-banner .slick-dots li{
            width: 10px;
            height: 10px;
            margin: 0 4px;
        }
        .home-hero-banner .slick-dots li button{
            width: 10px;
            height: 10px;
            padding: 0;
        }
        .home-hero-banner .slick-dots li button:before{
            width: 10px;
            height: 10px;
            font-size: 10px;
            color: rgba(255, 255, 255, 0.78);
            opacity: 1;
        }
        .home-hero-banner .slick-dots li.slick-active button:before{
            color: #ff6a00;
            opacity: 1;
        }
        .home-hero-side{
            padding: 18px 18px 14px;
            height: 100%;
            min-height: 408px;
        }
        .home-hero-side-title{
            font-size: 14px;
            line-height: 1.4;
            color: #111827;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .home-hero-side-text{
            font-size: 13px;
            line-height: 1.55;
            color: #6b7280;
            margin-bottom: 14px;
        }
        .home-hero-side-actions{
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            margin-bottom: 18px;
        }
        .home-hero-side-btn{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none !important;
        }
        .home-hero-side-btn.primary{
            background: linear-gradient(180deg, #ff7d1a 0%, #ff6400 100%);
            color: #fff !important;
            box-shadow: 0 10px 24px rgba(255, 106, 0, 0.2);
        }
        .home-hero-side-btn.secondary{
            border: 1px solid #eceef2;
            background: #fff;
            color: #ff6a00 !important;
        }
        .home-hero-benefits{
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .home-hero-benefit{
            display: grid;
            grid-template-columns: 36px minmax(0, 1fr);
            gap: 12px;
            align-items: start;
        }
        .home-hero-benefit-icon{
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid #ffd6bd;
            background: #fff7f0;
            color: #ff7a16;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .home-hero-benefit-title{
            font-size: 14px;
            line-height: 1.35;
            color: #111827;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .home-hero-benefit-text{
            font-size: 12px;
            line-height: 1.5;
            color: #6b7280;
        }
        .home-feature-strip-v2{
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            margin-top: 14px;
            overflow: hidden;
        }
        .home-feature-v2-item{
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 18px 16px;
            min-width: 0;
            position: relative;
        }
        .home-feature-v2-item:not(:last-child)::after{
            content: "";
            position: absolute;
            top: 18px;
            right: 0;
            width: 1px;
            height: calc(100% - 36px);
            background: #edf0f3;
        }
        .home-feature-v2-icon{
            width: 34px;
            height: 34px;
            flex: 0 0 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #1f2937;
        }
        .home-feature-v2-icon svg{
            width: 30px;
            height: 30px;
            display: block;
        }
        .home-feature-v2-icon .accent{
            stroke: #ff6a00;
        }
        .home-feature-v2-icon .base{
            stroke: #23262b;
        }
        .home-feature-v2-copy{
            min-width: 0;
        }
        .home-feature-v2-title{
            font-size: 14px;
            line-height: 1.3;
            color: #111827;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .home-feature-v2-text{
            font-size: 12px;
            line-height: 1.35;
            color: #6b7280;
        }
        #left-side-product-alert{
            display: none !important;
        }
        .home-banner-area > .container > .row,
        .home-banner-area > .layout-container > .row{
            position: relative;
        }
        .home-banner-area .aiz-category-menu .sub-cat-menu {
            width: calc(100% - 270px);
            left: 270px;
        }
        /* #auction_products .slick-slider .slick-list .slick-slide, */
        #section_home_categories .slick-slider .slick-list .slick-slide {
            margin-bottom: -4px;
        }
        .home-category-banner .home-category-name{
            bottom: -50px;
        }
        @media (min-width: 992px){
            .home-side-panel{
                width: 230px;
            }
        }
        @media (max-width: 991px){
            .home-banner-area .container,
            .home-banner-area .layout-container{
                min-width: 0;
                padding-left: 15px !important;
                padding-right: 15px!important;
            }
        }
        @media (max-width: 767px){
            #flash_deal .flash-deals-baner{
                height: 203px !important;
            }
        }
        .home-side-panel{
            background: #fff;
            border-radius: 14px;
            padding: 14px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
            box-sizing: border-box;
        }
        .home-side-card{
            border-radius: 14px;
            background: linear-gradient(180deg, #ffffff 0%, #fff8f1 100%);
            border: 1px solid #f5e7d8;
            padding: 16px;
            box-sizing: border-box;
        }
        .home-side-card + .home-side-card{
            margin-top: 14px;
        }
        .home-side-btn{
            display: block;
            width: 100%;
            border-radius: 999px;
            text-align: center;
            font-weight: 700;
            padding: 10px 16px;
            text-decoration: none !important;
        }
        .home-side-btn-primary{
            background: linear-gradient(180deg, #ff7a14 0%, #ff5a00 100%);
            color: #fff !important;
            box-shadow: 0 8px 18px rgba(255, 106, 0, 0.24);
        }
        .home-side-btn-outline{
            margin-top: 10px;
            border: 2px solid #ff8a3d;
            color: #ff6a00 !important;
            background: #fff;
        }
        .home-side-assurance-icon{
            width: 28px;
            height: 28px;
            border-radius: 8px;
            background: #fff1cc;
            color: #f59e0b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .home-side-link{
            color: #60a5fa !important;
            font-weight: 700;
            text-decoration: none !important;
        }
        .home-feature-strip{
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0;
            margin-top: 18px;
            padding: 16px 10px;
            background: #fff;
            border: 1px solid #f0f1f3;
            border-radius: 0;
            box-shadow: 0 3px 18px rgba(15, 23, 42, 0.06);
        }
        .home-feature-item{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 0;
            padding: 10px 18px;
            position: relative;
            text-align: center;
        }
        .home-feature-item:not(:last-child)::after{
            content: "";
            position: absolute;
            top: 10px;
            right: 0;
            width: 1px;
            height: calc(100% - 20px);
            background: #f1f3f5;
        }
        .home-feature-icon{
            flex: 0 0 42px;
            width: 42px;
            height: 42px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #222;
        }
        .home-feature-title{
            color: #1f1f1f;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.3;
            margin-bottom: 0;
        }
        .home-feature-text{
            color: #5f6368;
            font-size: 12px;
            line-height: 1.35;
        }
        .home-feature-copy{
            display: flex;
            flex-direction: column;
            gap: 4px;
            align-items: center;
        }
        .home-feature-icon svg{
            width: 40px;
            height: 40px;
            display: block;
        }
        .home-feature-icon .accent{
            stroke: #ff6a00;
        }
        .home-feature-icon .base{
            stroke: #23262b;
        }
        .home-showcase-panel{
            display: grid;
            grid-template-columns: minmax(0, 1.05fr) minmax(0, 1.65fr);
            gap: 0;
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .home-showcase-panel-single{
            grid-template-columns: minmax(0, 1fr);
        }
        .home-showcase-block{
            padding: 18px 20px 16px;
            min-width: 0;
        }
        .home-showcase-block + .home-showcase-block{
            border-left: 1px solid #f0f2f5;
        }
        .home-showcase-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .home-showcase-title{
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-showcase-link{
            color: #6b7280 !important;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-showcase-link:hover{
            color: #ff6a00 !important;
        }
        .home-category-grid{
            display: grid;
            grid-template-columns: repeat(10, minmax(0, 1fr));
            gap: 12px 10px;
        }
        .home-category-tile{
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            text-align: center;
            text-decoration: none !important;
            color: #1f2937 !important;
            padding: 6px 4px;
            border: 1px solid transparent;
            border-radius: 16px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .home-category-tile:hover{
            border-color: #ff6a00;
            box-shadow: 0 10px 24px rgba(255, 106, 0, 0.12);
            transform: translateY(-1px);
        }
        .home-category-thumb{
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: linear-gradient(180deg, #f9fafb 0%, #f3f4f6 100%);
            border: 1px solid #edf0f3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .home-category-thumb img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-category-icon{
            font-size: 24px;
            color: #9ca3af;
        }
        .home-category-name{
            font-size: 13px;
            font-weight: 600;
            line-height: 1.35;
            color: #374151;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-category-view-all{
            border: 1px dashed #d6dbe1;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            justify-content: center;
            min-height: 100%;
        }
        .home-category-view-all .home-category-thumb{
            background: #fff7ed;
            border-color: #fed7aa;
        }
        .home-category-view-all .home-category-icon{
            color: #ff6a00;
        }
        .home-category-view-all .home-category-name{
            font-weight: 700;
            color: #111827;
        }
        .home-product-strip{
            display: flex;
            gap: 14px;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            padding-bottom: 6px;
            scrollbar-width: thin;
            scrollbar-color: #d1d5db #f3f4f6;
        }
        .home-product-strip::-webkit-scrollbar{
            height: 8px;
        }
        .home-product-strip::-webkit-scrollbar-track{
            background: #f3f4f6;
            border-radius: 999px;
        }
        .home-product-strip::-webkit-scrollbar-thumb{
            background: #d1d5db;
            border-radius: 999px;
        }
        .home-scroll-target-locked{
            overflow-x: hidden;
            scrollbar-width: none;
            -ms-overflow-style: none;
            touch-action: none;
        }
        .home-scroll-target-locked::-webkit-scrollbar{
            display: none;
        }
        .home-industry-panel{
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 20px 18px;
            overflow: hidden;
        }
        .home-industry-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .home-industry-title{
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-industry-link{
            color: #6b7280 !important;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-industry-link:hover{
            color: #ff6a00 !important;
        }
        .home-industry-body{
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .home-scroll-row{
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .home-industry-arrow,
        .home-scroll-arrow{
            width: 36px;
            height: 36px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 36px;
            transition: all 0.2s ease;
        }
        .home-industry-arrow:hover,
        .home-scroll-arrow:hover{
            border-color: #ff6a00;
            color: #ff6a00;
            box-shadow: 0 8px 18px rgba(255, 106, 0, 0.14);
        }
        .home-industry-strip{
            display: flex;
            gap: 0;
            overflow-x: auto;
            overflow-y: hidden;
            scroll-behavior: smooth;
            flex: 1 1 auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .home-industry-strip::-webkit-scrollbar{
            display: none;
        }
        .home-industry-card{
            min-width: 120px;
            flex: 0 0 120px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 10px;
            padding: 12px 14px;
            text-decoration: none !important;
            color: #111827 !important;
            border-right: 1px solid #f1f3f5;
            scroll-snap-align: start;
        }
        .home-industry-card:first-child{
            border-left: 1px solid #f1f3f5;
        }
        .home-industry-card:hover{
            background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
        }
        .home-industry-icon{
            width: 46px;
            height: 46px;
            border-radius: 14px;
            background: linear-gradient(180deg, #fff8f1 0%, #fff1e8 100%);
            border: 1px solid #ffe0cc;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            color: #ff6a00;
            font-size: 22px;
        }
        .home-industry-icon img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-industry-name{
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            color: #1f2937;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-browse-panel{
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 20px 18px;
            overflow: hidden;
        }
        .home-browse-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }
        .home-browse-title{
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-browse-link{
            color: #ff6a00 !important;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-browse-link:hover{
            color: #e85d04 !important;
        }
        .home-browse-card{
            min-width: 188px;
            flex: 0 0 188px;
            border: 1px solid #eef2f6;
            border-radius: 18px;
            background: #fff;
            padding: 12px;
            color: #111827 !important;
            text-decoration: none !important;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            scroll-snap-align: start;
        }
        .home-browse-card:hover{
            border-color: #ffcfad;
            box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
            transform: translateY(-1px);
        }
        .home-browse-media{
            position: relative;
            aspect-ratio: 1 / 1;
            border-radius: 16px;
            background: #fafafa;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .home-browse-media img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-browse-wishlist{
            position: absolute;
            top: 8px;
            right: 8px;
            width: 28px;
            height: 28px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            background: rgba(255, 255, 255, 0.96);
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            padding: 0;
            appearance: none;
        }
        .home-browse-name{
            font-size: 13px;
            font-weight: 700;
            line-height: 1.4;
            color: #1f2937;
            min-height: 36px;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-browse-price{
            font-size: 15px;
            font-weight: 800;
            color: #111827;
            line-height: 1.3;
            margin-bottom: 4px;
        }
        .home-browse-meta{
            font-size: 11px;
            color: #6b7280;
            line-height: 1.45;
            margin-bottom: 4px;
        }
        .home-browse-origin{
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: #6b7280;
            line-height: 1.4;
            margin-bottom: 10px;
        }
        .home-browse-origin-dot{
            width: 8px;
            height: 8px;
            border-radius: 999px;
            background: #22c55e;
            display: inline-block;
        }
        .home-browse-action{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            width: 100%;
            border-radius: 999px;
            border: 1px solid #ffd6bf;
            background: #fffaf6;
            color: #ff6a00 !important;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
        }
        .home-browse-action:hover{
            background: #fff1e8;
        }
        .home-assurance-strip{
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0;
            background: #fffdf9;
            border: 1px solid #f5e5d8;
            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
            overflow: hidden;
            min-height: 126px;
        }
        .home-assurance-item{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 18px 12px;
            min-width: 0;
            position: relative;
            text-align: center;
        }
        .home-assurance-item:not(:last-child)::after{
            content: "";
            position: absolute;
            top: 14px;
            right: 0;
            width: 1px;
            height: calc(100% - 28px);
            background: #f2e8e0;
        }
        .home-assurance-icon{
            width: 38px;
            height: 38px;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #f59e0b;
        }
        .home-assurance-icon svg{
            width: 30px;
            height: 30px;
            display: block;
        }
        .home-assurance-copy{
            min-width: 0;
            width: 100%;
        }
        .home-assurance-title{
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            color: #111827;
            margin-bottom: 1px;
        }
        .home-assurance-text{
            font-size: 10px;
            line-height: 1.25;
            color: #6b7280;
        }
        .home-rfq-banner{
            position: relative;
            display: block;
        }
        .home-rfq-banner-btn{
            position: absolute;
            left: 54%;
            bottom: 28%;
            transform: translateX(-50%);
            min-height: 40px;
            padding: 0 24px;
            border-radius: 10px;
            background: linear-gradient(180deg, #ff8a1f 0%, #ff6a00 100%);
            color: #fff !important;
            font-size: 14px;
            font-weight: 700;
            line-height: 40px;
            text-decoration: none !important;
            white-space: nowrap;
            box-shadow: 0 12px 26px rgba(255, 106, 0, 0.28);
        }
        .home-rfq-banner-btn:hover{
            color: #fff !important;
            filter: brightness(0.98);
        }
        .home-product-card{
            display: block;
            color: #111827 !important;
            text-decoration: none !important;
            min-width: 180px;
            flex: 0 0 180px;
            border: 1px solid transparent;
            border-radius: 16px;
            padding: 8px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .home-product-card:hover{
            border-color: #ff6a00;
            box-shadow: 0 10px 24px rgba(255, 106, 0, 0.12);
            transform: translateY(-1px);
        }
        .home-product-media{
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 12px;
            background: #f8fafc;
            overflow: hidden;
            border: 1px solid #eef2f6;
            margin-bottom: 12px;
        }
        .home-product-media img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-product-name{
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
            color: #1f2937;
            min-height: 38px;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-product-price{
            font-size: 18px;
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
            margin-bottom: 6px;
        }
        .home-product-moq{
            font-size: 12px;
            color: #6b7280;
            line-height: 1.35;
        }
        .home-product-view-all{
            min-width: 180px;
            flex: 0 0 180px;
            border: 1px dashed #d6dbe1;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px 16px;
            color: #111827 !important;
            text-decoration: none !important;
        }
        .home-product-view-all-icon{
            width: 54px;
            height: 54px;
            border-radius: 999px;
            background: #fff7ed;
            color: #ff6a00;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .home-product-view-all-title{
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }
        .home-product-view-all-text{
            font-size: 12px;
            line-height: 1.4;
            color: #6b7280;
        }
        .home-wholesale-section{
            position: relative;
            background: #fff;
            border: 1px solid #edf0f3;
            border-radius: 18px;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 18px 16px 16px;
            overflow: hidden;
        }
        .home-wholesale-head{
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .home-wholesale-title{
            font-size: 28px;
            line-height: 1.1;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }
        .home-wholesale-subtitle{
            font-size: 14px;
            line-height: 1.45;
            color: #6b7280;
        }
        .home-wholesale-link{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #111827 !important;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-wholesale-link:hover{
            color: #ff6a00 !important;
        }
        .home-wholesale-carousel{
            position: relative;
        }
        .home-wholesale-arrow{
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            border-radius: 999px;
            border: 1px solid #edf0f3;
            background: #fff;
            color: #6b7280;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.1);
            z-index: 2;
        }
        .home-wholesale-arrow:hover{
            color: #ff6a00;
            border-color: #ffd4ba;
        }
        .home-wholesale-arrow.left{
            left: -6px;
        }
        .home-wholesale-arrow.right{
            right: -6px;
        }
        .home-wholesale-strip{
            display: flex;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            padding: 2px 28px 6px;
            scroll-behavior: smooth;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .home-wholesale-strip::-webkit-scrollbar{
            display: none;
        }
        .home-wholesale-card{
            min-width: 206px;
            flex: 0 0 206px;
            border: 1px solid #edf0f3;
            border-radius: 16px;
            background: #fff;
            padding: 12px;
            text-decoration: none !important;
            color: #111827 !important;
            box-shadow: 0 3px 14px rgba(15, 23, 42, 0.04);
        }
        .home-wholesale-card:hover{
            border-color: #ffd4ba;
            box-shadow: 0 12px 26px rgba(15, 23, 42, 0.08);
        }
        .home-wholesale-media{
            position: relative;
            height: 170px;
            border-radius: 12px;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .home-wholesale-media img{
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .home-wholesale-fav{
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            border-radius: 999px;
            background: #fff;
            border: 1px solid #edf0f3;
            color: #a1a1aa;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .home-wholesale-name{
            min-height: 44px;
            font-size: 13px;
            line-height: 1.4;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 8px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-wholesale-price{
            font-size: 14px;
            line-height: 1.25;
            font-weight: 800;
            color: #ff6a00;
            margin-bottom: 5px;
        }
        .home-wholesale-moq{
            font-size: 11px;
            line-height: 1.4;
            color: #6b7280;
            margin-bottom: 5px;
        }
        .home-wholesale-meta{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 4px;
        }
        .home-wholesale-rating{
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 11px;
            color: #ff6a00;
            white-space: nowrap;
        }
        .home-wholesale-rating i{
            font-size: 11px;
        }
        .home-wholesale-rating-count{
            color: #ff6a00;
        }
        .home-wholesale-country{
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            color: #6b7280;
            white-space: nowrap;
        }
        .home-wholesale-country .iti__flag{
            width: 14px;
            height: 10px;
            box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.08);
        }
        .home-wholesale-supplier{
            font-size: 11px;
            line-height: 1.4;
            color: #6b7280;
            margin-bottom: 10px;
        }
        .home-wholesale-supplier strong{
            color: #111827;
            font-weight: 700;
        }
        .home-wholesale-actions{
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
        }
        .home-wholesale-btn{
            min-height: 32px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 700;
        }
        .home-wholesale-btn.primary{
            background: linear-gradient(180deg, #ff7d1a 0%, #ff6400 100%);
            color: #fff !important;
        }
        .home-wholesale-btn.secondary{
            border: 1px solid #e7e9ee;
            background: #fff;
            color: #7c2d12 !important;
        }
        .home-wholesale-btn.disabled{
            opacity: 0.48;
            pointer-events: none;
        }
        .home-trade-preview{
            display: grid;
            grid-template-columns: minmax(0, 1.15fr) minmax(0, 1fr);
            gap: 14px;
        }
        .home-trade-panel{
            background: linear-gradient(180deg, #fffdfb 0%, #ffffff 100%);
            border: 1px solid #f4e6db;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.05);
            padding: 18px 18px 16px;
            min-width: 0;
        }
        .home-trade-panel-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .home-trade-panel-title{
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-trade-panel-meta{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #ef4444;
            font-weight: 700;
            flex-wrap: wrap;
        }
        .home-trade-countdown{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 800;
            color: #7f1d1d;
            padding: 8px 12px;
            border-radius: 999px;
            background: linear-gradient(135deg, #fff1f2 0%, #ffe4e6 100%);
            border: 1px solid #fecdd3;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
        }
        .home-trade-countdown-value{
            display: inline-flex;
            align-items: center;
            gap: 6px;
            letter-spacing: 0.02em;
            font-size: 18px;
            line-height: 1;
        }
        .home-trade-countdown-box{
            min-width: 42px;
            height: 42px;
            padding: 0 8px;
            border-radius: 12px;
            background: linear-gradient(180deg, #ffffff 0%, #ffe7ea 100%);
            border: 1px solid #fda4af;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(244, 63, 94, 0.12);
        }
        .home-trade-countdown-separator{
            font-size: 18px;
            font-weight: 900;
            color: #e11d48;
        }
        .home-trade-panel-link{
            color: #6b7280 !important;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-trade-panel-link:hover{
            color: #ff6a00 !important;
        }
        .home-trade-empty{
            min-height: 220px;
            border: 1px dashed #e5e7eb;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 24px 18px;
        }
        .home-trade-empty-icon{
            width: 52px;
            height: 52px;
            border-radius: 999px;
            background: #fff7ed;
            color: #ff6a00;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 12px;
        }
        .home-trade-empty-title{
            font-size: 16px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }
        .home-trade-empty-text{
            font-size: 12px;
            line-height: 1.5;
            color: #6b7280;
            max-width: 240px;
        }
        .home-trade-products{
            display: flex;
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 6px;
            scroll-snap-type: x proximity;
            scrollbar-width: thin;
            scrollbar-color: #ffb27d #fff1e8;
        }
        .home-trade-products::-webkit-scrollbar{
            height: 8px;
        }
        .home-trade-products::-webkit-scrollbar-track{
            background: #fff1e8;
            border-radius: 999px;
        }
        .home-trade-products::-webkit-scrollbar-thumb{
            background: linear-gradient(90deg, #ffb27d 0%, #ff7a14 100%);
            border-radius: 999px;
        }
        .home-trade-product-card{
            display: block;
            color: #111827 !important;
            text-decoration: none !important;
            min-width: 220px;
            flex: 0 0 220px;
            border: 1px solid transparent;
            border-radius: 16px;
            padding: 8px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            scroll-snap-align: start;
        }
        .home-trade-product-card:hover{
            border-color: #ff6a00;
            box-shadow: 0 10px 24px rgba(255, 106, 0, 0.12);
            transform: translateY(-1px);
        }
        .home-trade-product-image{
            position: relative;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #f1f3f5;
            overflow: hidden;
            aspect-ratio: 1 / 1;
            margin-bottom: 12px;
        }
        .home-trade-product-image img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-trade-product-discount{
            position: absolute;
            top: 8px;
            left: 8px;
            min-width: 38px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #ff5a5f;
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            line-height: 20px;
            text-align: center;
        }
        .home-trade-product-name{
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            color: #1f2937;
            min-height: 35px;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-trade-product-price{
            font-size: 14px;
            font-weight: 800;
            color: #ff6a00;
            margin-bottom: 2px;
        }
        .home-trade-product-old{
            font-size: 11px;
            color: #9ca3af;
            text-decoration: line-through;
            margin-bottom: 4px;
        }
        .home-trade-product-moq{
            font-size: 11px;
            color: #6b7280;
        }
        .home-trade-suppliers{
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }
        .home-trade-supplier-card{
            border: 1px solid #f1f3f5;
            border-radius: 16px;
            background: #fff;
            padding: 14px 12px;
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .home-trade-supplier-card:hover{
            border-color: #ff6a00;
            box-shadow: 0 10px 24px rgba(255, 106, 0, 0.12);
            transform: translateY(-1px);
        }
        .home-trade-supplier-top{
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }
        .home-trade-supplier-logo{
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            overflow: hidden;
            flex: 0 0 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ff6a00;
        }
        .home-trade-supplier-logo img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-trade-supplier-name{
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            color: #1f2937;
            margin-bottom: 6px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-trade-supplier-badge{
            font-size: 11px;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .home-trade-supplier-metrics{
            font-size: 11px;
            color: #6b7280;
            line-height: 1.55;
            margin-top: auto;
            margin-bottom: 12px;
        }
        .home-trade-supplier-btn{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 38px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            color: #111827 !important;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 700;
            background: #fff;
        }
        .home-trade-supplier-btn:hover{
            border-color: #ff6a00;
            color: #ff6a00 !important;
        }
        .home-leaderboard{
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }
        .home-leaderboard-row{
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 18px;
            min-width: 0;
        }
        .home-leaderboard-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .home-leaderboard-title{
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-leaderboard-link{
            color: #6b7280 !important;
            font-size: 12px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-leaderboard-link:hover{
            color: #ff6a00 !important;
        }
        .home-leaderboard-list{
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
        }
        .home-leaderboard-card{
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 10px;
            padding: 12px;
            border: 1px solid #eef2f6;
            border-radius: 16px;
            background: #fff;
            min-width: 0;
        }
        .home-leaderboard-logo{
            width: 54px;
            height: 54px;
            border-radius: 14px;
            overflow: hidden;
            background: #f8fafc;
            border: 1px solid #eef2f6;
            flex: 0 0 54px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #ff6a00;
            font-size: 22px;
        }
        .home-leaderboard-logo img{
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .home-leaderboard-body{
            min-width: 0;
            width: 100%;
        }
        .home-leaderboard-name{
            font-size: 14px;
            font-weight: 700;
            line-height: 1.35;
            color: #111827;
            margin-bottom: 5px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-leaderboard-meta{
            font-size: 11px;
            line-height: 1.45;
            color: #6b7280;
        }
        .home-leaderboard-rating{
            font-size: 11px;
            line-height: 1.4;
            color: #374151;
            margin-bottom: 4px;
        }
        .home-leaderboard-action{
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 34px;
            padding: 0 14px;
            border-radius: 999px;
            border: 1px solid #e5e7eb;
            color: #111827 !important;
            background: #fff;
            text-decoration: none !important;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }
        .home-leaderboard-action:hover{
            border-color: #ff6a00;
            color: #ff6a00 !important;
        }
        .home-leaderboard-empty{
            padding: 18px;
            border: 1px dashed #d6dbe1;
            border-radius: 16px;
            background: #f8fafc;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .home-infinite-products{
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 18px;
        }
        .home-infinite-products-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 16px;
        }
        .home-brand-row{
            background: #fff;
            border: 1px solid #edf0f3;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
            padding: 18px;
            overflow: hidden;
        }
        .home-brand-row-head{
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
        }
        .home-brand-row-title{
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-brand-row-link{
            color: #ff6a00 !important;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-brand-row-link:hover{
            color: #e85d04 !important;
        }
        .home-brand-row-grid{
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 12px;
        }
        .home-brand-row-card{
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-width: 0;
            padding: 14px 10px;
            text-align: center;
            color: #111827 !important;
            text-decoration: none !important;
            background: #fff;
            border: 1px solid #eef2f6;
            border-radius: 16px;
        }
        .home-brand-row-logo{
            width: 68px;
            height: 68px;
            border-radius: 16px;
            border: 1px solid #eef2f6;
            background: #fff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 10px;
        }
        .home-brand-row-logo img{
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }
        .home-brand-row-name{
            font-size: 13px;
            font-weight: 700;
            line-height: 1.35;
            color: #374151;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .home-infinite-products-title{
            font-size: 22px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 0;
        }
        .home-infinite-products-link{
            color: #ff6a00 !important;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none !important;
            white-space: nowrap;
        }
        .home-infinite-products-link:hover{
            color: #e85d04 !important;
        }
        .home-infinite-products-grid{
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 16px;
        }
        .home-infinite-product-item{
            min-width: 0;
        }
        .home-infinite-products-status{
            padding: 16px 0 4px;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
        }
        .home-infinite-products-loader{
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .home-infinite-products-loader::before{
            content: "";
            width: 16px;
            height: 16px;
            border-radius: 999px;
            border: 2px solid #fed7aa;
            border-top-color: #ff6a00;
            animation: homeInfiniteSpin 0.8s linear infinite;
        }
        .home-infinite-products-sentinel{
            width: 100%;
            height: 1px;
        }
        @keyframes homeInfiniteSpin{
            to{
                transform: rotate(360deg);
            }
        }
        @media (max-width: 1199px){
            .home-hero-stage{
                grid-template-columns: minmax(0, 1fr) 290px;
                min-height: 408px;
            }
            .home-hero-menu{
                display: none;
            }
            .home-feature-strip-v2{
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
            .home-feature-v2-item:nth-child(3)::after{
                display: none;
            }
            .home-trade-preview{
                grid-template-columns: 1fr;
            }
            .home-leaderboard{
                grid-template-columns: 1fr;
            }
            .home-leaderboard-list{
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .home-infinite-products-grid{
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            .home-brand-row-grid{
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
            .home-showcase-panel{
                grid-template-columns: 1fr;
            }
            .home-showcase-block + .home-showcase-block{
                border-left: 0;
                border-top: 1px solid #f0f2f5;
            }
            .home-category-grid{
                grid-template-columns: repeat(5, minmax(0, 1fr));
            }
            .home-feature-strip{
                grid-template-columns: repeat(3, minmax(0, 1fr));
                padding: 12px 8px;
            }
            .home-assurance-strip{
                grid-template-columns: repeat(3, minmax(0, 1fr));
                min-height: 116px;
            }
            .home-assurance-item{
                padding: 16px 10px;
            }
            .home-assurance-item:nth-child(3)::after{
                display: none;
            }
            .home-rfq-banner-btn{
                left: 50%;
                bottom: 20%;
            }
            .home-feature-item:nth-child(3)::after{
                display: none;
            }
        }
        @media (max-width: 767px){
            .home-hero-stage{
                grid-template-columns: 1fr;
                min-height: 0;
            }
            .home-hero-banner{
                min-height: 320px;
                height: auto;
            }
            .home-hero-slide{
                height: 320px;
                min-height: 320px;
            }
            .home-hero-overlay{
                padding: 30px 24px 44px;
                max-width: 86%;
            }
            .home-hero-title{
                font-size: 28px;
                margin-bottom: 12px;
            }
            .home-hero-text{
                font-size: 14px;
                line-height: 1.55;
                margin-bottom: 18px;
            }
            .home-hero-side{
                padding: 16px;
                min-height: 0;
            }
            .home-hero-side-actions{
                grid-template-columns: 1fr 1fr;
            }
            .home-feature-strip-v2{
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
            .home-feature-v2-item{
                justify-content: flex-start;
                padding: 14px 12px;
            }
            .home-feature-v2-item:nth-child(even)::after{
                display: none;
            }
            .home-leaderboard-row{
                padding: 14px;
            }
            .home-leaderboard-title{
                font-size: 18px;
            }
            .home-leaderboard-list{
                grid-template-columns: 1fr;
            }
            .home-leaderboard-card{
                gap: 10px;
                padding: 10px;
            }
            .home-leaderboard-logo{
                width: 48px;
                height: 48px;
                flex-basis: 48px;
                border-radius: 12px;
            }
            .home-leaderboard-name{
                font-size: 13px;
            }
            .home-infinite-products{
                padding: 14px;
            }
            .home-brand-row{
                padding: 14px;
            }
            .home-brand-row-title{
                font-size: 18px;
            }
            .home-brand-row-grid{
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
            .home-brand-row-logo{
                width: 58px;
                height: 58px;
                border-radius: 14px;
            }
            .home-infinite-products-title{
                font-size: 18px;
            }
            .home-infinite-products-grid{
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 12px;
            }
            .home-industry-panel{
                padding: 16px 14px;
            }
            .home-industry-title{
                font-size: 18px;
            }
            .home-industry-body{
                gap: 8px;
            }
            .home-industry-arrow,
            .home-scroll-arrow{
                width: 32px;
                height: 32px;
                flex-basis: 32px;
            }
            .home-industry-card{
                min-width: 102px;
                flex-basis: 102px;
                padding: 10px 10px;
            }
            .home-industry-icon{
                width: 40px;
                height: 40px;
                border-radius: 12px;
                font-size: 20px;
            }
            .home-industry-name{
                font-size: 12px;
            }
            .home-browse-panel{
                padding: 16px 14px;
            }
            .home-browse-title{
                font-size: 18px;
            }
            .home-browse-card{
                min-width: 158px;
                flex-basis: 158px;
                border-radius: 16px;
                padding: 10px;
            }
            .home-assurance-strip{
                grid-template-columns: repeat(2, minmax(0, 1fr));
                min-height: 0;
            }
            .home-assurance-item{
                padding: 14px 10px;
            }
            .home-assurance-item:nth-child(even)::after{
                display: none;
            }
            .home-assurance-title{
                font-size: 11px;
            }
            .home-assurance-text{
                font-size: 9px;
            }
            .home-assurance-icon{
                width: 32px;
                height: 32px;
                flex-basis: 32px;
            }
            .home-assurance-icon svg{
                width: 26px;
                height: 26px;
            }
            .home-rfq-banner-btn{
                left: 50%;
                bottom: 10%;
                min-height: 34px;
                padding: 0 18px;
                border-radius: 8px;
                font-size: 12px;
                line-height: 34px;
            }
            .home-trade-panel{
                padding: 14px;
            }
            .home-trade-panel-head{
                align-items: flex-start;
            }
            .home-trade-panel-title{
                font-size: 18px;
            }
            .home-trade-products{
                gap: 10px;
            }
            .home-trade-product-card{
                min-width: 176px;
                flex-basis: 176px;
            }
            .home-trade-countdown{
                padding: 6px 10px;
            }
            .home-trade-countdown-value,
            .home-trade-countdown-separator{
                font-size: 16px;
            }
            .home-trade-countdown-box{
                min-width: 36px;
                height: 36px;
                border-radius: 10px;
            }
            .home-trade-suppliers{
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 10px;
            }
            .home-showcase-block{
                padding: 14px 14px 12px;
            }
            .home-showcase-title{
                font-size: 18px;
            }
            .home-category-grid{
                grid-template-columns: repeat(3, minmax(0, 1fr));
                gap: 10px 8px;
            }
            .home-product-card,
            .home-product-view-all{
                min-width: 150px;
                flex-basis: 150px;
            }
            .home-feature-strip{
                grid-template-columns: repeat(1, minmax(0, 1fr));
                padding: 10px 0;
            }
            .home-feature-item{
                padding: 12px 16px;
            }
            .home-feature-item::after{
                display: none;
            }
        }
    </style>
    @php $lang = get_system_language()->code;  @endphp
    @php
        $deliveryCountryKey = session('delivery_country_code') ?: session('delivery_country_name') ?: 'global';
        $homeCacheKeySuffix = \Illuminate\Support\Str::slug((string) $deliveryCountryKey, '_');
        $showcaseCategories = \Illuminate\Support\Facades\Cache::remember('home_showcase_categories', 3600, function () {
            return \App\Models\Category::query()
                ->where('level', 0)
                ->where('digital', 0)
                ->orderBy('order_level')
                ->orderBy('name')
                ->take(9)
                ->get();
        });
        $industryCategories = \Illuminate\Support\Facades\Cache::remember('home_industry_categories', 3600, function () {
            return \App\Models\Category::query()
                ->where('level', 0)
                ->where('digital', 0)
                ->orderByDesc('featured')
                ->orderBy('order_level')
                ->take(10)
                ->get();
        });
        $browseProducts = \Illuminate\Support\Facades\Cache::remember('home_browse_products_' . $homeCacheKeySuffix, 1800, function () {
            return filter_products(\App\Models\Product::query())
                ->latest()
                ->limit(8)
                ->get();
        });
        $showcaseFeaturedProducts = \Illuminate\Support\Facades\Cache::remember('home_showcase_featured_products_' . $homeCacheKeySuffix, 1800, function () {
            return filter_products(
                \App\Models\Product::query()
                    ->where('auction_product', 0)
                    ->where('wholesale_product', 1)
                    ->with(['thumbnail', 'publicSupplierCompany', 'supplierB2bCompany', 'user.shop'])
            )
                ->latest()
                ->limit(6)
                ->get();
        });
        $showcaseProductsViewAllUrl = $showcaseFeaturedProducts->isNotEmpty()
            ? route('search')
            : route('search');
        $showcaseProductsViewAllText = $showcaseFeaturedProducts->isNotEmpty()
            ? translate('Browse wholesale catalog')
            : translate('Browse all products');
        $bannerFlashDeal = \Illuminate\Support\Facades\Cache::remember('home_banner_flash_deal', 600, function () {
            return get_featured_flash_deal();
        });
        if (
            !$bannerFlashDeal
            && \Illuminate\Support\Facades\Schema::hasTable('flash_deals')
        ) {
            $bannerFlashDeal = \Illuminate\Support\Facades\Cache::remember('home_banner_flash_deal_fallback', 600, function () {
                return \App\Models\FlashDeal::query()
                    ->where('status', 1)
                    ->latest()
                    ->first();
            });
        }
        $bannerFlashProducts = $bannerFlashDeal
            ? \Illuminate\Support\Facades\Cache::remember('home_banner_flash_products_' . $bannerFlashDeal->id, 600, function () use ($bannerFlashDeal) {
                return collect(get_flash_deal_products($bannerFlashDeal->id))->take(4);
            })
            : collect();
        $bannerVerifiedSuppliers = collect($tradeServicesData['featured_suppliers_list'] ?? collect())->take(4);
        if (
            $bannerVerifiedSuppliers->isEmpty()
            && \Illuminate\Support\Facades\Schema::hasTable('b2b_companies')
        ) {
            $bannerVerifiedSuppliers = \Illuminate\Support\Facades\Cache::remember('home_banner_verified_suppliers_' . $homeCacheKeySuffix, 1800, function () {
                return apply_selected_delivery_country_to_suppliers(
                    \App\Models\B2BCompany::query()
                )
                    ->publicSuppliers()
                    ->where('verified_supplier_badge', true)
                    ->orderByDesc('profile_score')
                    ->latest()
                    ->limit(4)
                    ->get();
            });
        }
        $topRatedSellers = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('shops')) {
            $topRatedSellers = \Illuminate\Support\Facades\Cache::remember('home_top_rated_sellers', 1800, function () {
                return \App\Models\Shop::query()
                    ->where('verification_status', 1)
                    ->orderByDesc('rating')
                    ->orderByDesc('num_of_reviews')
                    ->limit(6)
                    ->get();
            });
        }
        $topSuppliers = collect($tradeServicesData['featured_suppliers_list'] ?? collect())
            ->sortByDesc(function ($supplier) {
                return (float) ($supplier->profile_score ?? 0);
            })
            ->take(6)
            ->values();
        if (
            $topSuppliers->isEmpty()
            && \Illuminate\Support\Facades\Schema::hasTable('b2b_companies')
        ) {
            $topSuppliers = \Illuminate\Support\Facades\Cache::remember('home_top_suppliers_' . $homeCacheKeySuffix, 1800, function () {
                return apply_selected_delivery_country_to_suppliers(
                    \App\Models\B2BCompany::query()
                )
                    ->publicSuppliers()
                    ->orderByDesc('profile_score')
                    ->orderByDesc('response_rate')
                    ->latest()
                    ->limit(6)
                    ->get();
            });
        }
    @endphp
    @php
        $heroAppName = config('app.name');
        $heroCategories = get_level_zero_categories()->take(8);
        $heroBenefits = [
            [
                'icon' => 'las la-clipboard-list',
                'title' => translate('Post RFQ'),
                'text' => translate('Get quotes from verified suppliers'),
                'url' => route('b2b.rfqs.create'),
            ],
            [
                'icon' => 'las la-user-check',
                'title' => translate('Verified Suppliers'),
                'text' => translate('Trusted & professional sellers'),
                'url' => route('b2b.suppliers.index'),
            ],
            [
                'icon' => 'las la-shield-alt',
                'title' => translate('Trade Assurance'),
                'text' => translate('Secure orders & payments'),
                'url' => route('home'),
            ],
            [
                'icon' => 'las la-shipping-fast',
                'title' => translate('Global Shipping'),
                'text' => translate('Fast & reliable delivery'),
                'url' => route('home'),
            ],
            [
                'icon' => 'las la-headset',
                'title' => translate('24/7 Support'),
                'text' => translate('We are here to help'),
                'url' => route('home'),
            ],
        ];
    @endphp
    <!-- home banner area -->
    <div class="home-banner-area mb-3" style="">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-hero-stage position-relative">
                <div class="home-hero-menu d-none d-xl-block">
                    <div class="home-hero-menu-head">
                        <i class="las la-th-large"></i>
                        <span>{{ translate('All Categories') }}</span>
                    </div>
                    <div class="aiz-category-menu home-hero-category-menu" style="width:230px;">
                        <ul class="list-unstyled categories no-scrollbar mb-0 text-left">
                            @foreach ($heroCategories as $category)
                                @php
                                    $category_name = $category->getTranslation('name');
                                @endphp
                                <li class="category-nav-element" data-id="{{ $category->id }}">
                                    <a href="{{ route('products.category', $category->slug) }}">
                                        <span class="d-inline-flex align-items-center minw-0">
                                            <img class="cat-image lazyload" src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                data-src="{{ isset($category->catIcon->file_name) ? my_asset($category->catIcon->file_name) : static_asset('assets/img/placeholder.jpg') }}" alt="{{ $category_name }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            <span class="cat-name has-transition">{{ $category_name }}</span>
                                        </span>
                                    </a>
                                    <div class="sub-cat-menu more c-scrollbar-light border p-4 shadow-none">
                                        <div class="c-preloader text-center absolute-center">
                                            <i class="las la-spinner la-spin la-3x opacity-70"></i>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                            <li class="category-nav-element">
                                <a href="{{ route('categories.all') }}" class="home-hero-view-all">
                                    <span class="d-inline-flex align-items-center minw-0">
                                        <i class="las la-th-large mr-2"></i>
                                        <span class="cat-name has-transition">{{ translate('View all categories') }}</span>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="home-hero-center">
                    <div class="home-slider home-hero-banner">
                        @if (get_setting('home_slider_images', null, $lang) != null)
                            <div class="aiz-carousel overflow-hidden" data-autoplay="true" data-infinite="true" data-dots="true">
                                @php
                                    $decoded_slider_images = json_decode(get_setting('home_slider_images', null, $lang), true);
                                    $sliders = get_slider_images($decoded_slider_images);
                                    $home_slider_links = get_setting('home_slider_links', null, $lang);
                                @endphp
                                @foreach ($sliders as $key => $slider)
                                    <div class="carousel-box">
                                        <a class="home-hero-slide" href="{{ isset(json_decode($home_slider_links, true)[$key]) ? json_decode($home_slider_links, true)[$key] : '' }}">
                                            <img
                                                src="{{ $slider ? my_asset($slider->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                alt="{{ env('APP_NAME')}} promo"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"
                                            >
                                            <span class="home-hero-overlay">
                                                <span class="home-hero-title">{{ translate('Source Globally') }}<br>{{ translate('Sell Anywhere') }}</span>
                                                <span class="home-hero-text">{{ translate('Millions of products. Thousands of verified suppliers. One trusted global marketplace.') }}</span>
                                                <span class="home-hero-cta">{{ translate('Explore Now') }}</span>
                                            </span>
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <a class="home-hero-slide" href="{{ route('categories.all') }}">
                                <img
                                    src="{{ static_asset('assets/img/pages/home-reclassic.webp') }}"
                                    alt="{{ env('APP_NAME')}} promo"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';"
                                >
                                <span class="home-hero-overlay">
                                    <span class="home-hero-title">{{ translate('Source Globally') }}<br>{{ translate('Sell Anywhere') }}</span>
                                    <span class="home-hero-text">{{ translate('Millions of products. Thousands of verified suppliers. One trusted global marketplace.') }}</span>
                                    <span class="home-hero-cta">{{ translate('Explore Now') }}</span>
                                </span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="home-hero-side">
                    <div class="home-hero-side-title">{{ translate('Welcome to') }} {{ $heroAppName }}</div>
                    <div class="home-hero-side-text">{{ translate('Join millions of buyers & grow your business globally.') }}</div>
                    <div class="home-hero-side-actions">
                        <a href="{{ route('user.registration') }}" class="home-hero-side-btn primary">{{ translate('Join Free') }}</a>
                        <a href="{{ route('user.login') }}" class="home-hero-side-btn secondary">{{ translate('Sign In') }}</a>
                    </div>
                    <div class="home-hero-benefits">
                        @foreach ($heroBenefits as $heroBenefit)
                            <a href="{{ $heroBenefit['url'] }}" class="home-hero-benefit text-reset text-decoration-none">
                                <span class="home-hero-benefit-icon"><i class="{{ $heroBenefit['icon'] }}"></i></span>
                                <span>
                                    <span class="d-block home-hero-benefit-title">{{ $heroBenefit['title'] }}</span>
                                    <span class="d-block home-hero-benefit-text">{{ $heroBenefit['text'] }}</span>
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="home-feature-strip-v2">
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M16 5.2 24.5 8.6v7.8c0 5.1-3.4 8.5-8.5 10.4-5.1-1.9-8.5-5.3-8.5-10.4V8.6L16 5.2Z" stroke-width="1.8" stroke-linejoin="round"/>
                            <path class="accent" d="m12.5 16.3 2.4 2.5 4.8-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('Verified Suppliers') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Strict verification process') }}</div>
                    </div>
                </div>
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M6 9.5h20v13H6z" stroke-width="1.8" stroke-linejoin="round"/>
                            <path class="accent" d="M10 16h6M20.5 16h1.5M18 16h.5" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('Secure Payment') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Multiple safe payment options') }}</div>
                    </div>
                </div>
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M16 5.2 24.5 8.6v7.8c0 5.1-3.4 8.5-8.5 10.4-5.1-1.9-8.5-5.3-8.5-10.4V8.6L16 5.2Z" stroke-width="1.8" stroke-linejoin="round"/>
                            <path class="accent" d="M16 9.5v7m0 0 3 3m-3-3-3 3" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('Trade Assurance') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Refund for order issues') }}</div>
                    </div>
                </div>
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M5.5 10.5h12.8v8.8H5.5zM18.3 13h4.4l3.3 3.2v3.1h-7.7z" stroke-width="1.8" stroke-linejoin="round"/>
                            <circle class="base" cx="10.2" cy="22.2" r="1.8" stroke-width="1.8"/>
                            <circle class="accent" cx="22.6" cy="22.2" r="1.8" stroke-width="1.8"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('Global Shipping') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Worldwide logistics') }}</div>
                    </div>
                </div>
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M16 5.2 24.5 8.6v7.8c0 5.1-3.4 8.5-8.5 10.4-5.1-1.9-8.5-5.3-8.5-10.4V8.6L16 5.2Z" stroke-width="1.8" stroke-linejoin="round"/>
                            <path class="accent" d="m12.5 16.3 2.4 2.5 4.8-5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('Buyer Protection') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Money back guarantee') }}</div>
                    </div>
                </div>
                <div class="home-feature-v2-item">
                    <span class="home-feature-v2-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path class="base" d="M9 16a7 7 0 1 1 14 0" stroke-width="1.8" stroke-linecap="round"/>
                            <path class="base" d="M9 16H7.8a2.3 2.3 0 0 0-2.3 2.3v2.2a2.3 2.3 0 0 0 2.3 2.3H10V16Zm14 0h1.2a2.3 2.3 0 0 1 2.3 2.3v2.2a2.3 2.3 0 0 1-2.3 2.3H22V16Z" stroke-width="1.8" stroke-linejoin="round"/>
                            <path class="accent" d="M21.4 24.5c-.9.8-2.4 1.4-4.4 1.4h-2" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div class="home-feature-v2-copy">
                        <div class="home-feature-v2-title">{{ translate('24/7 Support') }}</div>
                        <div class="home-feature-v2-text">{{ translate('Dedicated support') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-showcase-panel home-showcase-panel-single">
                <div class="home-showcase-block">
                    <div class="home-showcase-head">
                        <h3 class="home-showcase-title">{{ translate('Shop by category') }}</h3>
                    </div>
                    <div class="home-category-grid">
                        @foreach ($showcaseCategories->take(9) as $category)
                            @php
                                $categoryImage = $category->banner ? uploaded_asset($category->banner) : ($category->icon ? uploaded_asset($category->icon) : null);
                            @endphp
                            <a href="{{ route('products.category', $category->slug) }}" class="home-category-tile position-relative hov-animate-outline">
                                <span class="home-category-thumb">
                                    @if ($categoryImage)
                                        <img src="{{ $categoryImage }}" alt="{{ $category->name }}">
                                    @else
                                        <i class="las la-th-large home-category-icon"></i>
                                    @endif
                                </span>
                                <span class="home-category-name">{{ $category->getTranslation('name') }}</span>
                            </a>
                        @endforeach
                        <a href="{{ route('categories.all') }}" class="home-category-tile home-category-view-all position-relative hov-animate-outline">
                            <span class="home-category-thumb">
                                <i class="las la-arrow-right home-category-icon"></i>
                            </span>
                            <span class="home-category-name">{{ translate('View all') }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="home-wholesale-section mt-3">
                <div class="home-wholesale-head">
                    <div>
                        <div class="home-wholesale-title">{{ translate('Featured Wholesale Products') }}</div>
                        <div class="home-wholesale-subtitle">{{ translate('High quality products with competitive wholesale prices') }}</div>
                    </div>
                    <a href="{{ $showcaseProductsViewAllUrl }}" class="home-wholesale-link">
                        <span>{{ translate('View all') }}</span>
                        <i class="las la-arrow-right"></i>
                    </a>
                </div>
                <div class="home-wholesale-carousel">
                    <button type="button" class="home-wholesale-arrow left" data-scroll-target="featured-products-strip" data-scroll-direction="left" aria-label="{{ translate('Scroll left') }}">
                        <i class="las la-angle-left"></i>
                    </button>
                    <div class="home-wholesale-strip home-scroll-target-locked" id="featured-products-strip">
                        @foreach ($showcaseFeaturedProducts as $product)
                            @php
                                $productSupplierSummary = getProductSupplierSummary($product);
                                $productCardReview = product_card_review_summary($product);
                                $productPriceLabel = product_card_price_label($product);
                                $productRfqUrl = getProductRfqUrl($product);
                                $productContactUrl = $productSupplierSummary['url'] ?? null;
                                $productCountryLabel = $productSupplierSummary['country'] ?: translate('Global');
                            @endphp
                            <div class="home-wholesale-card">
                                <a href="{{ route('product', $product->slug) }}" class="text-reset text-decoration-none d-block">
                                    <div class="home-wholesale-media">
                                        <span class="home-wholesale-fav"><i class="lar la-heart"></i></span>
                                        <img src="{{ $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : static_asset('assets/img/placeholder.jpg') }}"
                                            alt="{{ $product->getTranslation('name') }}"
                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                    </div>
                                    <div class="home-wholesale-name">{{ $product->getTranslation('name') }}</div>
                                    <div class="home-wholesale-price">{{ $productPriceLabel }}</div>
                                    <div class="home-wholesale-moq">{{ translate('MOQ') }}: {{ $product->min_qty ? $product->min_qty : 1 }} {{ translate('Pieces') }}</div>
                                    <div class="home-wholesale-meta">
                                        <span class="home-wholesale-rating">
                                            <i class="las la-star"></i>
                                            <span>{{ number_format((float) ($productCardReview['average'] ?? 0), 1) }}</span>
                                            <span class="home-wholesale-rating-count">({{ $productCardReview['count'] ?? 0 }})</span>
                                        </span>
                                        <span class="home-wholesale-country">
                                            @if (!empty($productSupplierSummary['country_flag_iso']))
                                                <span class="iti__flag iti__{{ $productSupplierSummary['country_flag_iso'] }}"></span>
                                            @endif
                                            <span>{{ $productCountryLabel }}</span>
                                        </span>
                                    </div>
                                    <div class="home-wholesale-supplier">
                                        <strong>{{ translate('Verified supplier') }}</strong>
                                    </div>
                                </a>
                                <div class="home-wholesale-actions">
                                    <a href="{{ $productRfqUrl ?: 'javascript:void(0)' }}" class="home-wholesale-btn primary {{ $productRfqUrl ? '' : 'disabled' }}">{{ translate('RFQ') }}</a>
                                    <a href="{{ $productContactUrl ?: 'javascript:void(0)' }}" class="home-wholesale-btn secondary {{ $productContactUrl ? '' : 'disabled' }}">{{ translate('Contact') }}</a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="home-wholesale-arrow right" data-scroll-target="featured-products-strip" data-scroll-direction="right" aria-label="{{ translate('Scroll right') }}">
                        <i class="las la-angle-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
    <!-- Banner section 1 -->
    @php $homeBanner1Images = get_setting('home_banner1_images', null, $lang);   @endphp
    @if ($homeBanner1Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                @php
                    $banner_1_imags = json_decode($homeBanner1Images);
                    $data_md = count($banner_1_imags) >= 2 ? 2 : 1;
                    $home_banner1_links = get_setting('home_banner1_links', null, $lang);
                @endphp
                <div class="w-100">
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_1_imags) }}" data-xxl-items="{{ count($banner_1_imags) }}"
                        data-xl-items="{{ count($banner_1_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_1_imags as $key => $value)
                            <div class="carousel-box overflow-hidden hov-scale-img">
                                <a href="{{ isset(json_decode($home_banner1_links, true)[$key]) ? json_decode($home_banner1_links, true)[$key] : '' }}"
                                    class="d-block text-reset rounded-2 overflow-hidden">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                        class="img-fluid lazyload w-100 has-transition"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Flash Deal -->
    @php
        $flash_deal = \Illuminate\Support\Facades\Cache::remember('home_flash_deal_section_deal', 600, function () {
            return get_featured_flash_deal();
        });
        $flash_deal_bg = get_setting('flash_deal_bg_color');
    @endphp
    @if ($flash_deal != null)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3" id="flash_deal">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="rounded-2 overflow-hidden p-3 p-md-2rem @if(get_setting('flash_deal_section_outline') == 1) border @endif" style="background: {{ $flash_deal_bg != null ? $flash_deal_bg : '#fff9ed' }}; border-color: {{ get_setting('flash_deal_section_outline_color') }} !important;">
                    <!-- Top Section -->
                    <div class="d-flex flex-wrap align-items-baseline justify-content-center justify-content-sm-between mb-2 mb-md-3 position-relative">
                        <div class="d-flex flex-wrap align-items-center">
                            <!-- Title -->
                            <h3 class="fs-22 fs-md-20 fw-700 mb-2 mb-sm-0">
                                <span class="d-inline-block">{{ translate('Flash Sale') }}</span>
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="24" viewBox="0 0 16 24"
                                    class="ml-2">
                                    <path id="Path_28795" data-name="Path 28795"
                                        d="M30.953,13.695a.474.474,0,0,0-.424-.25h-4.9l3.917-7.81a.423.423,0,0,0-.028-.428.477.477,0,0,0-.4-.207H21.588a.473.473,0,0,0-.429.263L15.041,18.151a.423.423,0,0,0,.034.423.478.478,0,0,0,.4.2h4.593l-2.229,9.683a.438.438,0,0,0,.259.5.489.489,0,0,0,.571-.127L30.9,14.164a.425.425,0,0,0,.054-.469Z"
                                        transform="translate(-15 -5)" fill="#fcc201" />
                                </svg>
                            </h3>
                            <!-- Countdown -->
                            <div class="aiz-count-down align-items-center ml-2 mb-2 mb-lg-0" data-date="{{ date('Y/m/d H:i:s', $flash_deal->end_date) }}"></div>
                        </div>
                        <!-- Links -->
                        <div>
                            <div class="text-dark d-flex align-items-center mb-0">
                                <a href="{{ route('flash-deals') }}"
                                    class="fs-10 fs-md-12 fw-700 has-transition text-reset opacity-60 hov-opacity-100 hov-text-primary animate-underline-primary mr-3">{{ translate('View All Flash Sale') }}</a>
                                <span class=" border-left border-soft-light border-width-2 pl-3">
                                    <a href="{{ route('flash-deal-details', $flash_deal->slug) }}"
                                        class="fs-10 fs-md-12 fw-700 has-transition text-reset opacity-60 hov-opacity-100 hov-text-primary animate-underline-primary">{{ translate('View All Products from This Flash Sale') }}</a>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="row gutters-16 align-items-center">
                        <!-- Flash Deals Baner -->
                        <div class="col-auto">
                            <a href="{{ route('flash-deal-details', $flash_deal->slug) }}">
                                <div class=" size-180px size-md-200px size-lg-280px rounded-2 overflow-hidden"
                                    style="background-image: url('{{ uploaded_asset($flash_deal->banner) }}'); background-size: cover; background-position: center center;">
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <!-- Flash Deals Products -->
                            @php
                                $flash_deal_products = \Illuminate\Support\Facades\Cache::remember('home_flash_deal_section_products_' . $flash_deal->id, 600, function () use ($flash_deal) {
                                    return get_flash_deal_products($flash_deal->id);
                                });
                            @endphp
                            <div class="pr-md-3">
                                <div class="aiz-carousel gutters-16 arrow-inactive-none arrow-x-0"
                                data-items="5" data-xxl-items="5" data-xl-items="4" data-lg-items="3" data-md-items="2.5"
                                data-sm-items="2.3" data-xs-items="1" data-arrows="true" data-dots="false">
                                @foreach ($flash_deal_products as $key => $flash_deal_product)
                                    <div class="carousel-box position-relative has-transition hov-animate-outline">
                                        @if ($flash_deal_product->product != null && $flash_deal_product->product->published != 0)
                                            @php
                                                $product_url = route('product', $flash_deal_product->product->slug);
                                                if ($flash_deal_product->product->auction_product == 1) {
                                                    $product_url = route('auction-product', $flash_deal_product->product->slug);
                                                }
                                            @endphp
                                            <div
                                                class="aiz-card-box h-180px h-md-200px h-lg-280px flash-deal-item position-relative text-center">
                                                <a href="{{ $product_url }}"
                                                    class="d-block overflow-hidden hov-scale-img"
                                                    title="{{ $flash_deal_product->product->getTranslation('name') }}">
                                                    <!-- Image -->
                                                    <img src="{{ get_image($flash_deal_product->product->thumbnail) }}"
                                                        class="lazyload h-100px h-md-120px h-lg-170px mw-100 mx-auto has-transition rounded-2"
                                                        alt="{{ $flash_deal_product->product->getTranslation('name') }}"
                                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                    <!-- Product name -->
                                                    <h3 class="fw-400 fs-13 text-truncate-2 lh-1-4 mb-0 h-40px text-center pt-1 px-1 mt-1">
                                                        <a href="{{ $product_url }}" class="d-block text-reset hov-text-primary"
                                                            title="{{ $flash_deal_product->product->getTranslation('name') }}">{{ $flash_deal_product->product->getTranslation('name') }}</a>
                                                    </h3>
                                                    <!-- Price -->
                                                    <h4 class="fs-14 d-flex justify-content-center mt-3">
                                                        @if ($flash_deal_product->auction_product == 0)
                                                            <!-- Previous price -->
                                                            @if (home_base_price($flash_deal_product->product) != home_discounted_base_price($flash_deal_product->product))
                                                                <span class="disc-amount has-transition">
                                                                    <del class="fw-400 text-secondary mr-1">{{ home_base_price($flash_deal_product->product) }}</del>
                                                                </span>
                                                            @endif
                                                            <!-- price -->
                                                            <span class="fw-700 text-primary">{{ home_discounted_base_price($flash_deal_product->product) }}</span>
                                                        @endif
                                                    </h4>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <!-- Featured Products -->
    <div id="section_featured">
    </div>
    @if (addon_is_activated('preorder'))
        <!-- Banner Section 2 -->
        @php $homepreorder_banner_1Images = get_setting('home_preorder_banner_1_images', null, $lang);   @endphp
        @if ($homepreorder_banner_1Images != null)
            <div class="mb-2 mb-md-3 mt-2 mt-md-3">
                <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                    @php
                        $banner_2_imags = json_decode($homepreorder_banner_1Images);
                        $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                        $home_preorder_banner_1_links = get_setting('home_preorder_banner_1_links', null, $lang);
                    @endphp
                    <div class="rounded-2 overflow-hidden">
                        <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                        data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                        data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                        data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                        data-dots="false">
                        @foreach ($banner_2_imags as $key => $value)
                            <div class="carousel-box overflow-hidden hov-scale-img">
                                <a href="{{ isset(json_decode($home_preorder_banner_1_links, true)[$key]) ? json_decode($home_preorder_banner_1_links, true)[$key] : '' }}"
                                    class="d-block text-reset rounded-2 overflow-hidden">
                                    <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                        data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                        class="img-fluid lazyload w-100 has-transition"
                                        onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                </a>
                            </div>
                        @endforeach
                    </div>
                    </div>
                </div>
            </div>
        @endif
    
        <!-- Featured Preorder Products -->
        <div id="section_featured_preorder_products">
        </div>
    @endif
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-trade-preview">
                <div class="home-trade-panel">
                    <div class="home-trade-panel-head">
                        <div class="d-flex align-items-center flex-wrap">
                            <h3 class="home-trade-panel-title mr-3">{{ translate('Flash Deals') }}</h3>
                            @if ($bannerFlashDeal)
                                <div class="home-trade-panel-meta">
                                    <span>{{ translate('Ends in') }}</span>
                                    <div class="home-trade-countdown" data-simple-countdown="{{ date('Y/m/d H:i:s', $bannerFlashDeal->end_date) }}">
                                        <span class="home-trade-countdown-value">
                                            <span class="home-trade-countdown-box">00</span>
                                            <span class="home-trade-countdown-separator">:</span>
                                            <span class="home-trade-countdown-box">00</span>
                                            <span class="home-trade-countdown-separator">:</span>
                                            <span class="home-trade-countdown-box">00</span>
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                        <a href="{{ route('flash-deals') }}" class="home-trade-panel-link">{{ translate('View all') }}</a>
                    </div>
                    @if ($bannerFlashProducts->isNotEmpty())
                        <div class="home-scroll-row">
                            <button type="button" class="home-scroll-arrow" data-scroll-target="flash-deals-strip" data-scroll-direction="left" aria-label="{{ translate('Scroll left') }}">
                                <i class="las la-angle-left"></i>
                            </button>
                            <div class="home-trade-products home-scroll-target-locked" id="flash-deals-strip">
                                @foreach ($bannerFlashProducts as $flashDealProduct)
                                    @if ($flashDealProduct->product)
                                        @php
                                            $product = $flashDealProduct->product;
                                            $discountValue = discount_in_percentage($product);
                                        @endphp
                                        <a href="{{ route('product', $product->slug) }}" class="home-trade-product-card position-relative hov-animate-outline">
                                            <div class="home-trade-product-image">
                                                @if ($discountValue > 0)
                                                    <span class="home-trade-product-discount">-{{ $discountValue }}%</span>
                                                @endif
                                                <img src="{{ $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : static_asset('assets/img/placeholder.jpg') }}"
                                                    alt="{{ $product->getTranslation('name') }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            </div>
                                            <div class="home-trade-product-name">{{ $product->getTranslation('name') }}</div>
                                            <div class="home-trade-product-price">{{ home_discounted_base_price($product) }}</div>
                                            <div class="home-trade-product-old">{{ home_base_price($product) }}</div>
                                            <div class="home-trade-product-moq">
                                                {{ translate('Min. order') }}: {{ $product->min_qty ? $product->min_qty : 1 }}
                                            </div>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                            <button type="button" class="home-scroll-arrow" data-scroll-target="flash-deals-strip" data-scroll-direction="right" aria-label="{{ translate('Scroll right') }}">
                                <i class="las la-angle-right"></i>
                            </button>
                        </div>
                    @else
                        <div class="home-trade-empty">
                            <span class="home-trade-empty-icon"><i class="las la-bolt"></i></span>
                            <div class="home-trade-empty-title">{{ translate('Coming soon') }}</div>
                            <div class="home-trade-empty-text">{{ translate('Flash deal items will appear here when a deal becomes available.') }}</div>
                        </div>
                    @endif
                </div>
                <div class="home-trade-panel">
                    <div class="home-trade-panel-head">
                        <h3 class="home-trade-panel-title">{{ translate('Verified Suppliers') }}</h3>
                        <a href="{{ route('b2b.suppliers.index', ['verified_supplier_badge' => 1]) }}" class="home-trade-panel-link">{{ translate('View all') }}</a>
                    </div>
                    @if ($bannerVerifiedSuppliers->isNotEmpty())
                        <div class="home-trade-suppliers">
                            @foreach ($bannerVerifiedSuppliers as $supplier)
                                <div class="home-trade-supplier-card position-relative hov-animate-outline">
                                    <div class="home-trade-supplier-top">
                                        <span class="home-trade-supplier-logo">
                                            @if ($supplier->logo)
                                                <img src="{{ uploaded_asset($supplier->logo) }}" alt="{{ $supplier->company_name }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            @else
                                                <i class="las la-industry"></i>
                                            @endif
                                        </span>
                                        <div class="minw-0">
                                            <div class="home-trade-supplier-name">{{ $supplier->company_name }}</div>
                                            <div class="home-trade-supplier-badge">{{ translate('Verified') }}</div>
                                        </div>
                                    </div>
                                    <div class="home-trade-supplier-metrics">
                                        <div>{{ number_format((float) ($supplier->response_rate ?? 0), 1) }}% {{ translate('response') }}</div>
                                        <div>{{ (int) ($supplier->profile_score ?? 0) }} {{ translate('profile score') }}</div>
                                        <div>{{ $supplier->country ?: translate('Global') }}</div>
                                    </div>
                                    <a href="{{ $supplier->public_slug ? route('b2b.suppliers.show', $supplier->public_slug) : route('b2b.suppliers.index') }}" class="home-trade-supplier-btn">
                                        {{ translate('Chat Now') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="home-trade-empty">
                            <span class="home-trade-empty-icon"><i class="las la-store-alt"></i></span>
                            <div class="home-trade-empty-title">{{ translate('Coming soon') }}</div>
                            <div class="home-trade-empty-text">{{ translate('Verified supplier cards will appear here once approved suppliers are available.') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    <!-- Banner Section 2 -->
    @php $homeBanner2Images = get_setting('home_banner2_images', null, $lang);   @endphp
    @if ($homeBanner2Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                @php
                    $banner_2_imags = json_decode($homeBanner2Images);
                    $data_md = count($banner_2_imags) >= 2 ? 2 : 1;
                    $home_banner2_links = get_setting('home_banner2_links', null, $lang);
                @endphp
                <div class="rounded-2 overflow-hidden">
                    <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_2_imags) }}" data-xxl-items="{{ count($banner_2_imags) }}"
                    data-xl-items="{{ count($banner_2_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_2_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner2_links, true)[$key]) ? json_decode($home_banner2_links, true)[$key] : '' }}"
                                class="d-block text-reset rounded-2 overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
                </div>
            </div>
        </div>
    @endif
    <!-- Best Selling  -->
    <div id="section_best_selling">
    </div>
    <!-- New Products -->
    <div id="section_newest">
    </div>
    @if ($industryCategories->isNotEmpty())
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="home-industry-panel">
                    <div class="home-industry-head">
                        <h3 class="home-industry-title">{{ translate('Shop by industry') }}</h3>
                        <a href="{{ route('categories.all') }}" class="home-industry-link">{{ translate('View all') }} <i class="las la-arrow-right"></i></a>
                    </div>
                    <div class="home-industry-body">
                        <button type="button" class="home-industry-arrow" data-scroll-target="shop-industry-strip" data-scroll-direction="left" aria-label="{{ translate('Scroll left') }}">
                            <i class="las la-angle-left"></i>
                        </button>
                        <div class="home-industry-strip" id="shop-industry-strip">
                            @foreach ($industryCategories as $industryCategory)
                                @php
                                    $industryImage = $industryCategory->icon ? uploaded_asset($industryCategory->icon) : ($industryCategory->banner ? uploaded_asset($industryCategory->banner) : null);
                                @endphp
                                <a href="{{ route('products.category', $industryCategory->slug) }}" class="home-industry-card position-relative hov-animate-outline">
                                    <span class="home-industry-icon">
                                        @if ($industryImage)
                                            <img src="{{ $industryImage }}" alt="{{ $industryCategory->getTranslation('name') }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                        @else
                                            <i class="las la-th-large"></i>
                                        @endif
                                    </span>
                                    <span class="home-industry-name">{{ $industryCategory->getTranslation('name') }}</span>
                                </a>
                            @endforeach
                        </div>
                        <button type="button" class="home-industry-arrow" data-scroll-target="shop-industry-strip" data-scroll-direction="right" aria-label="{{ translate('Scroll right') }}">
                            <i class="las la-angle-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    @endif
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-browse-panel">
                <div class="home-browse-head">
                    <h3 class="home-browse-title">{{ translate('Recommended products for you') }}</h3>
                    <a href="{{ route('search', ['sort_by' => 'newest']) }}" class="home-browse-link">{{ translate('View all') }} <i class="las la-arrow-right"></i></a>
                </div>
                @if ($browseProducts->isNotEmpty())
                    <div class="home-scroll-row">
                        <button type="button" class="home-scroll-arrow" data-scroll-target="browse-products-strip" data-scroll-direction="left" aria-label="{{ translate('Scroll left') }}">
                            <i class="las la-angle-left"></i>
                        </button>
                        <div class="home-product-strip home-scroll-target-locked" id="browse-products-strip">
                            @foreach ($browseProducts as $product)
                                @php
                                    $productCountry = $product->country_name ?: translate('Global');
                                @endphp
                                <div class="home-browse-card position-relative hov-animate-outline">
                                    <a href="{{ route('product', $product->slug) }}" class="d-block text-reset text-decoration-none">
                                        <div class="home-browse-media">
                                            <img src="{{ $product->thumbnail_img ? uploaded_asset($product->thumbnail_img) : static_asset('assets/img/placeholder.jpg') }}"
                                                alt="{{ $product->getTranslation('name') }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            <button type="button" class="home-browse-wishlist" onclick="addToWishList({{ $product->id }})" aria-label="{{ translate('Add to wishlist') }}">
                                                <i class="lar la-heart"></i>
                                            </button>
                                        </div>
                                        <div class="home-browse-name">{{ $product->getTranslation('name') }}</div>
                                        <div class="home-browse-price">{{ home_discounted_base_price($product) }}</div>
                                        <div class="home-browse-meta">
                                            {{ translate('MOQ') }}: {{ $product->min_qty ? $product->min_qty : 1 }}
                                            {{ translate('pieces') }}
                                        </div>
                                        <div class="home-browse-origin">
                                            <span class="home-browse-origin-dot"></span>
                                            <span>{{ $productCountry }}</span>
                                        </div>
                                    </a>
                                    <a href="javascript:void(0)" class="home-browse-action" @if (Auth::check() || get_Setting('guest_checkout_activation') == 1) onclick="addToCartSingleProduct({{ $product->id }})" @else onclick="showLoginModal()" @endif>
                                        {{ translate('Add to cart') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                        <button type="button" class="home-scroll-arrow" data-scroll-target="browse-products-strip" data-scroll-direction="right" aria-label="{{ translate('Scroll right') }}">
                            <i class="las la-angle-right"></i>
                        </button>
                    </div>
                @else
                    <div class="home-trade-empty">
                        <span class="home-trade-empty-icon"><i class="las la-box-open"></i></span>
                        <div class="home-trade-empty-title">{{ translate('Products are coming soon') }}</div>
                        <div class="home-trade-empty-text">{{ translate('Recommended items will appear here when published products are available.') }}</div>
                    </div>
                @endif
            </div>
        </div>
    </section>
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-assurance-strip">
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M16 5.5 23.8 8.7v7.2c0 4.7-3.1 7.8-7.8 9.6-4.7-1.8-7.8-4.9-7.8-9.6V8.7L16 5.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="m13.4 15.9 2 2.1 4.1-4.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('Trade Assurance') }}</div>
                        <div class="home-assurance-text">{{ translate('Secure your orders') }}</div>
                    </div>
                </div>
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M8 7.5h16v17H8z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="M12 12h8M12 16h5M12 20h8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('RFQ') }}</div>
                        <div class="home-assurance-text">{{ translate('Get multiple quotes') }}</div>
                    </div>
                </div>
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M16 5.5 23.8 8.7v7.2c0 4.7-3.1 7.8-7.8 9.6-4.7-1.8-7.8-4.9-7.8-9.6V8.7L16 5.5Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="m13.4 15.9 2 2.1 4.1-4.3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('Supplier Verification') }}</div>
                        <div class="home-assurance-text">{{ translate('Trusted & verified') }}</div>
                    </div>
                </div>
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M10 8.5h12l4 4v7l-4 4H10l-4-4v-7l4-4Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="m13.2 16 1.8 1.8 3.8-3.8" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('Inspection Services') }}</div>
                        <div class="home-assurance-text">{{ translate('Quality you can trust') }}</div>
                    </div>
                </div>
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M6.5 11.5h19v12h-19z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="M10.5 11.5V9.8A2.8 2.8 0 0 1 13.3 7h5.4a2.8 2.8 0 0 1 2.8 2.8v1.7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                            <path d="M6.5 16h19" stroke="currentColor" stroke-width="1.9"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('Secure Payments') }}</div>
                        <div class="home-assurance-text">{{ translate('Safe & protected') }}</div>
                    </div>
                </div>
                <div class="home-assurance-item position-relative hov-animate-outline">
                    <span class="home-assurance-icon" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M16 6.5 24 9.4v6.9c0 4.5-3 7.4-8 9.2-5-1.8-8-4.7-8-9.2V9.4l8-2.9Z" stroke="currentColor" stroke-width="1.9" stroke-linejoin="round"/>
                            <path d="M13.3 15.7h5.4M16 13v5.4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <div class="home-assurance-copy">
                        <div class="home-assurance-title">{{ translate('Buyer Protection') }}</div>
                        <div class="home-assurance-text">{{ translate('Money back guarantee') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Banner Section 3 -->
    @php $homeBanner3Images = get_setting('home_banner3_images', null, $lang);   @endphp
    @if ($homeBanner3Images != null)
        <div class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                @php
                    $banner_3_imags = json_decode($homeBanner3Images);
                    $data_md = count($banner_3_imags) >= 2 ? 2 : 1;
                    $home_banner3_links = get_setting('home_banner3_links', null, $lang);
                @endphp
                <div class="aiz-carousel gutters-16 overflow-hidden arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="{{ count($banner_3_imags) }}" data-xxl-items="{{ count($banner_3_imags) }}"
                    data-xl-items="{{ count($banner_3_imags) }}" data-lg-items="{{ $data_md }}"
                    data-md-items="{{ $data_md }}" data-sm-items="1" data-xs-items="1" data-arrows="true"
                    data-dots="false">
                    @foreach ($banner_3_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner3_links, true)[$key]) ? json_decode($home_banner3_links, true)[$key] : '' }}"
                                class="d-block text-reset rounded-2 overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
    @if ($topRatedSellers->isNotEmpty() || $topSuppliers->isNotEmpty())
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                <div class="home-leaderboard">
                    <div class="home-leaderboard-row">
                        <div class="home-leaderboard-head">
                            <h3 class="home-leaderboard-title">{{ translate('Top Sellers') }}</h3>
                            <a href="{{ route('sellers') }}" class="home-leaderboard-link">{{ translate('View all') }}</a>
                        </div>
                        @if ($topRatedSellers->isNotEmpty())
                            <div class="home-leaderboard-list">
                                @foreach ($topRatedSellers as $seller)
                                    <div class="home-leaderboard-card position-relative hov-animate-outline">
                                        <span class="home-leaderboard-logo">
                                            @if ($seller->logo)
                                                <img src="{{ uploaded_asset($seller->logo) }}" alt="{{ $seller->name }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            @else
                                                <i class="las la-store"></i>
                                            @endif
                                        </span>
                                        <div class="home-leaderboard-body">
                                            <div class="home-leaderboard-name">{{ $seller->name }}</div>
                                            <div class="home-leaderboard-rating">
                                                {{ number_format((float) ($seller->rating ?? 0), 1) }}/5
                                                ({{ (int) ($seller->num_of_reviews ?? 0) }} {{ translate('reviews') }})
                                            </div>
                                            <div class="home-leaderboard-meta">{{ translate('Verified seller') }}</div>
                                        </div>
                                        <a href="{{ route('shop.visit', $seller->slug) }}" class="home-leaderboard-action">{{ translate('Visit') }}</a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="home-leaderboard-empty">{{ translate('Top seller cards will appear here once verified sellers are available.') }}</div>
                        @endif
                    </div>
                    <div class="home-leaderboard-row">
                        <div class="home-leaderboard-head">
                            <h3 class="home-leaderboard-title">{{ translate('Top Suppliers') }}</h3>
                            <a href="{{ route('b2b.suppliers.index') }}" class="home-leaderboard-link">{{ translate('View all') }}</a>
                        </div>
                        @if ($topSuppliers->isNotEmpty())
                            <div class="home-leaderboard-list">
                                @foreach ($topSuppliers as $supplier)
                                    <div class="home-leaderboard-card position-relative hov-animate-outline">
                                        <span class="home-leaderboard-logo">
                                            @if ($supplier->logo)
                                                <img src="{{ uploaded_asset($supplier->logo) }}" alt="{{ $supplier->company_name }}"
                                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            @else
                                                <i class="las la-industry"></i>
                                            @endif
                                        </span>
                                        <div class="home-leaderboard-body">
                                            <div class="home-leaderboard-name">{{ $supplier->company_name }}</div>
                                            <div class="home-leaderboard-rating">
                                                {{ (int) ($supplier->profile_score ?? 0) }} {{ translate('profile score') }}
                                            </div>
                                            <div class="home-leaderboard-meta">
                                                {{ number_format((float) ($supplier->response_rate ?? 0), 1) }}% {{ translate('response rate') }}
                                                · {{ $supplier->country ?: translate('Global') }}
                                            </div>
                                        </div>
                                        <a href="{{ $supplier->public_slug ? route('b2b.suppliers.show', $supplier->public_slug) : route('b2b.suppliers.index') }}" class="home-leaderboard-action">{{ translate('View') }}</a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="home-leaderboard-empty">{{ translate('Top supplier cards will appear here once approved suppliers are available.') }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
    @php $homeBanner4Images = get_setting('home_banner4_images', null, $lang); @endphp
    @if ($homeBanner4Images != null)
        <section class="mb-2 mb-md-3 mt-2 mt-md-3">
            <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                @php
                    $banner_4_imags = json_decode($homeBanner4Images);
                    $home_banner4_links = get_setting('home_banner4_links', null, $lang);
                @endphp
                <div class="aiz-carousel overflow-hidden rounded-2 arrow-inactive-none arrow-dark arrow-x-15"
                    data-items="1" data-arrows="true" data-dots="false" data-autoplay="true">
                    @foreach ($banner_4_imags as $key => $value)
                        <div class="carousel-box overflow-hidden hov-scale-img">
                            <a href="{{ isset(json_decode($home_banner4_links, true)[$key]) ? json_decode($home_banner4_links, true)[$key] : '' }}"
                                class="home-rfq-banner d-block text-reset rounded-2 overflow-hidden">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($value) }}" alt="{{ env('APP_NAME') }} promo"
                                    class="img-fluid lazyload w-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                                <span class="home-rfq-banner-btn">
                                    {{ translate('Submit RFQ Now') }} <i class="las la-arrow-right fs-12 align-middle ml-1"></i>
                                </span>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
    @php
        $brands_section_bg = get_setting('brands_section_bg_color');
        $top_brands = json_decode(get_setting('top_brands'));
        $brands = \Illuminate\Support\Facades\Cache::remember('home_brands_row', 3600, function () use ($top_brands) {
            return !empty($top_brands)
                ? get_brands($top_brands)
                : \App\Models\Brand::query()->latest()->take(6)->get();
        });
    @endphp
    @if (count($brands) > 0)
            <section class="mb-2 mb-md-3 mt-2 mt-md-3">
                <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                    <div class="p-3 p-md-2rem rounded-2 @if(get_setting('brands_section_outline') == 1) border @endif"
                        style="background: {{ $brands_section_bg != null ? $brands_section_bg : '#f0f2f5' }}; border-color: {{ get_setting('brands_section_outline_color') }} !important; padding-bottom: 1rem !important;">
                        <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                            <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">{{ translate('Top Brands') }}</h3>
                            <div class="d-flex">
                                <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                                    href="{{ route('brands.all') }}">{{ translate('View All Brands') }}</a>
                            </div>
                        </div>
                        <div class="row gutters-16">
                            @foreach ($brands->take(6) as $brand)
                                <div class="col-xl-2 col-lg-2 col-md-4 col-6 my-3">
                                    <a href="{{ route('products.brand', $brand->slug) }}" class="d-block has-transition hov-shadow-out z-1 hov-scale-img rounded-2 overflow-hidden position-relative hov-animate-outline">
                                        <span class="d-flex flex-column align-items-center justify-content-center text-center p-2">
                                            <span class="d-flex align-items-center justify-content-center bg-white size-80px p-2 overflow-hidden rounded-circle">
                                                <img src="{{ $brand->logo != null ? uploaded_asset($brand->logo) : static_asset('assets/img/placeholder.jpg') }}"
                                                class="lazyload w-100 has-transition"
                                                alt="{{ $brand->getTranslation('name') }}"
                                                onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                            </span>
                                            <span class="d-block text-center text-dark fs-12 fs-md-14 fw-700 mt-2">
                                                {{ $brand->getTranslation('name') }}
                                            </span>
                                        </span>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
    @endif
    <section class="mb-2 mb-md-3 mt-2 mt-md-3">
        <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
            <div class="home-infinite-products">
                <div class="home-infinite-products-head">
                    <h3 class="home-infinite-products-title">{{ translate('More Products') }}</h3>
                    <a href="{{ route('search', ['sort_by' => 'newest']) }}" class="home-infinite-products-link">{{ translate('View all') }}</a>
                </div>
                <div
                    id="home-infinite-products-grid"
                    class="home-infinite-products-grid"
                    data-next-page="1"
                    data-limit="30"
                    data-loading="false"
                    data-complete="false">
                </div>
                <div id="home-infinite-products-status" class="home-infinite-products-status">
                    <span class="home-infinite-products-loader">{{ translate('Loading products') }}</span>
                </div>
                <div id="home-infinite-products-sentinel" class="home-infinite-products-sentinel" aria-hidden="true"></div>
            </div>
        </div>
    </section>
    <!-- Auction Product -->
    @if (addon_is_activated('auction'))
        <div id="auction_products">
        </div>
    @endif
    <!-- Cupon -->
    @if (get_setting('coupon_system') == 1)
        <div class="" style="background-color: {{ get_setting('cupon_background_color', '#fff9ed') }}">
            <div class="container">
                <div class="position-relative py-5">
                    <div class="text-center text-xl-left position-relative z-5">
                        <div class="d-lg-flex">
                            <div class="mb-3 mb-lg-0">
                                <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="109.602" height="93.34" viewBox="0 0 109.602 93.34">
                                    <defs>
                                      <clipPath id="clip-path">
                                        <path id="Union_10" data-name="Union 10" d="M12263,13778v-15h64v-41h12v56Z" transform="translate(-11966 -8442.865)" fill="none" stroke="var(--{{ get_setting('cupon_text_color') }})" stroke-width="2"/>
                                      </clipPath>
                                    </defs>
                                    <g id="Group_25375" data-name="Group 25375" transform="translate(-274.201 -5254.611)">
                                      <g id="Mask_Group_23" data-name="Mask Group 23" transform="translate(-3652.459 1785.452) rotate(-45)" clip-path="url(#clip-path)">
                                        <g id="Group_24322" data-name="Group 24322" transform="translate(207 18.136)">
                                          <g id="Subtraction_167" data-name="Subtraction 167" transform="translate(-12177 -8458)" fill="none">
                                            <path d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z" stroke="none"/>
                                            <path d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z" stroke="none" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          </g>
                                        </g>
                                      </g>
                                      <g id="Group_24321" data-name="Group 24321" transform="translate(-3514.477 1653.317) rotate(-45)">
                                        <g id="Subtraction_167-2" data-name="Subtraction 167" transform="translate(-12177 -8458)" fill="none">
                                          <path d="M12335,13770h-56a8.009,8.009,0,0,1-8-8v-8a8,8,0,0,0,0-16v-8a8.009,8.009,0,0,1,8-8h56a8.009,8.009,0,0,1,8,8v8a8,8,0,0,0,0,16v8A8.009,8.009,0,0,1,12335,13770Z" stroke="none"/>
                                          <path d="M 12335.0009765625 13768.0009765625 C 12338.3095703125 13768.0009765625 12341.0009765625 13765.30859375 12341.0009765625 13762 L 12341.0009765625 13755.798828125 C 12336.4423828125 13754.8701171875 12333.0009765625 13750.8291015625 12333.0009765625 13746 C 12333.0009765625 13741.171875 12336.4423828125 13737.130859375 12341.0009765625 13736.201171875 L 12341.0009765625 13729.9990234375 C 12341.0009765625 13726.6904296875 12338.3095703125 13723.9990234375 12335.0009765625 13723.9990234375 L 12278.9990234375 13723.9990234375 C 12275.6904296875 13723.9990234375 12272.9990234375 13726.6904296875 12272.9990234375 13729.9990234375 L 12272.9990234375 13736.201171875 C 12277.5576171875 13737.1298828125 12280.9990234375 13741.1708984375 12280.9990234375 13746 C 12280.9990234375 13750.828125 12277.5576171875 13754.869140625 12272.9990234375 13755.798828125 L 12272.9990234375 13762 C 12272.9990234375 13765.30859375 12275.6904296875 13768.0009765625 12278.9990234375 13768.0009765625 L 12335.0009765625 13768.0009765625 M 12335.0009765625 13770.0009765625 L 12278.9990234375 13770.0009765625 C 12274.587890625 13770.0009765625 12270.9990234375 13766.412109375 12270.9990234375 13762 L 12270.9990234375 13754 C 12275.4111328125 13753.9990234375 12278.9990234375 13750.4111328125 12278.9990234375 13746 C 12278.9990234375 13741.5888671875 12275.41015625 13738 12270.9990234375 13738 L 12270.9990234375 13729.9990234375 C 12270.9990234375 13725.587890625 12274.587890625 13721.9990234375 12278.9990234375 13721.9990234375 L 12335.0009765625 13721.9990234375 C 12339.412109375 13721.9990234375 12343.0009765625 13725.587890625 12343.0009765625 13729.9990234375 L 12343.0009765625 13738 C 12338.5888671875 13738.0009765625 12335.0009765625 13741.5888671875 12335.0009765625 13746 C 12335.0009765625 13750.4111328125 12338.58984375 13754 12343.0009765625 13754 L 12343.0009765625 13762 C 12343.0009765625 13766.412109375 12339.412109375 13770.0009765625 12335.0009765625 13770.0009765625 Z" stroke="none" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                        </g>
                                        <g id="Group_24325" data-name="Group 24325">
                                          <rect id="Rectangle_18578" data-name="Rectangle 18578" width="8" height="2" transform="translate(120 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18579" data-name="Rectangle 18579" width="8" height="2" transform="translate(132 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18581" data-name="Rectangle 18581" width="8" height="2" transform="translate(144 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                          <rect id="Rectangle_18580" data-name="Rectangle 18580" width="8" height="2" transform="translate(108 5287)" fill="var(--{{ get_setting('cupon_text_color') }})"/>
                                        </g>
                                      </g>
                                    </g>
                                </svg>
                            </div>
                            <div class="ml-lg-3">
                                <h5 class="fs-36 fw-700 text-{{ get_setting('cupon_text_color') }} mb-3">{{ translate(get_setting('cupon_title')) }}</h5>
                                <h5 class="fs-20 fw-400 text-{{ get_setting('cupon_text_color') }}">{{ translate(get_setting('cupon_subtitle')) }}</h5>
                                <div class="mt-5 pt-5">
                                    <a href="{{ route('coupons.all') }}" class="btn btn-secondary rounded-2 fs-16 px-4"
                                        style="box-shadow: 0px 20px 30px rgba(0, 0, 0, 0.16);">{{ translate('View All Coupons') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="position-absolute right-0 bottom-0 h-100">
                        <img class="img-fit h-100" src="{{ uploaded_asset(get_setting('coupon_background_image', null, $lang)) }}"
                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/coupon.svg') }}';"
                            alt="{{ env('APP_NAME') }} promo">
                    </div>
                </div>
            </div>
        </div>
    @endif
    @if (addon_is_activated('preorder'))
        <!-- Newest Preorder Products -->
        @include('preorder.frontend.home_page.newest_preorder')
    @endif
    <!-- Classified Product -->
    @if (get_setting('classified_product') == 1)
        @php
            $classified_products = \Illuminate\Support\Facades\Cache::remember('home_classified_products_6', 1800, function () {
                return get_home_page_classified_products(6);
            });
            $classified_section_bg = get_setting('classified_section_bg_color');
        @endphp
        @if (count($classified_products) > 0)
            <section class="mb-2 mb-md-3 mt-2rem">
                <div class="@if (get_setting('show_full_width_header') == 'on') layout-container mx-auto px-3 @else container @endif">
                    <div class="p-3 p-md-2rem rounded-2 overflow-hidden @if(get_setting('classified_section_outline') == 1) border @endif"
                        style="background: {{ $classified_section_bg != null ? $classified_section_bg : '#fff9ed' }}; border-color: {{ get_setting('classified_section_outline_color') }} !important;">
                        <!-- Top Section -->
                        <div class="d-flex mb-2 mb-md-3 align-items-baseline justify-content-between">
                            <!-- Title -->
                            <h3 class="fs-16 fs-md-20 fw-700 mb-2 mb-sm-0">
                                <span class="">{{ translate('Classified Ads') }}</span>
                            </h3>
                            <!-- Links -->
                            <div class="d-flex">
                                <a class="text-blue fs-10 fs-md-12 fw-700 hov-text-primary animate-underline-primary"
                                    href="{{ route('customer.products') }}">{{ translate('View All Products') }}</a>
                            </div>
                        </div>
                        <!-- Banner -->
                        @php
                            $classifiedBannerImage = get_setting('classified_banner_image', null, $lang);
                            $classifiedBannerImageSmall = get_setting('classified_banner_image_small', null, $lang);
                        @endphp
                        @if ($classifiedBannerImage != null || $classifiedBannerImageSmall != null)
                            <div class="mb-3 rounded-2 overflow-hidden hov-scale-img d-none d-md-block">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ uploaded_asset($classifiedBannerImage) }}"
                                    alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </div>
                            <div class="mb-3 rounded-2 overflow-hidden hov-scale-img d-md-none">
                                <img src="{{ static_asset('assets/img/placeholder-rect.jpg') }}"
                                    data-src="{{ $classifiedBannerImageSmall != null ? uploaded_asset($classifiedBannerImageSmall) : uploaded_asset($classifiedBannerImage) }}"
                                    alt="{{ env('APP_NAME') }} promo" class="lazyload img-fit h-100 has-transition"
                                    onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder-rect.jpg') }}';">
                            </div>
                        @endif
                        <!-- Products Section -->
                        <div class="">
                            <div class="row gutters-16">
                                @foreach ($classified_products as $key => $classified_product)
                                    <div
                                        class="col-xxl-4 col-md-6 has-transition hov-shadow-out z-1">
                                        <div class="aiz-card-box py-2 has-transition">
                                            <div class="row hov-scale-img">
                                                <div class="col-4 col-md-5 mb-3 mb-md-0">
                                                    <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                        class="d-block rounded-2 overflow-hidden h-auto h-md-150px text-center">
                                                        <img class="img-fluid lazyload mx-auto has-transition"
                                                            src="{{ static_asset('assets/img/placeholder.jpg') }}"
                                                            data-src="{{ isset($classified_product->thumbnail->file_name) ? my_asset($classified_product->thumbnail->file_name) : static_asset('assets/img/placeholder.jpg') }}"
                                                            alt="{{ $classified_product->getTranslation('name') }}"
                                                            onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
                                                    </a>
                                                </div>
                                                <div class="col py-2">
                                                    <h3
                                                        class="fw-400 fs-14 text-dark text-truncate-2 lh-1-4 mb-3 h-35px d-none d-sm-block">
                                                        <a href="{{ route('customer.product', $classified_product->slug) }}"
                                                            class="d-block text-reset hov-text-primary">{{ $classified_product->getTranslation('name') }}</a>
                                                    </h3>
                                                    <div class="d-md-flex d-lg-block justify-content-between">
                                                        <div class="fs-14 mb-3">
                                                            <span
                                                                class="text-secondary">{{ $classified_product->user ? $classified_product->user->name : '' }}</span><br>
                                                            <span
                                                                class="fw-700 text-primary">{{ single_price($classified_product->unit_price) }}</span>
                                                        </div>
                                                        @if ($classified_product->conditon == 'new')
                                                            <span
                                                                class="badge badge-md badge-inline badge-soft-info fs-13 fw-700 px-3 text-info"
                                                                style="border-radius: 20px;">{{ translate('New') }}</span>
                                                        @elseif($classified_product->conditon == 'used')
                                                            <span
                                                                class="badge badge-md badge-inline badge-soft-danger fs-13 fw-700 px-3 text-danger"
                                                                style="border-radius: 20px;">{{ translate('Used') }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    @endif
    <script>
        (function () {
            function pad(value) {
                return String(value).padStart(2, '0');
            }
            function renderCountdownMarkup(hours, minutes, seconds) {
                return '<span class="home-trade-countdown-box">' + pad(hours) + '</span>' +
                    '<span class="home-trade-countdown-separator">:</span>' +
                    '<span class="home-trade-countdown-box">' + pad(minutes) + '</span>' +
                    '<span class="home-trade-countdown-separator">:</span>' +
                    '<span class="home-trade-countdown-box">' + pad(seconds) + '</span>';
            }
            function startCountdown(element) {
                var endDateValue = element.getAttribute('data-simple-countdown');
                var valueEl = element.querySelector('.home-trade-countdown-value');
                if (!endDateValue || !valueEl) {
                    return;
                }
                var normalized = endDateValue.replace(/-/g, '/');
                var endTime = new Date(normalized).getTime();
                if (Number.isNaN(endTime)) {
                    valueEl.innerHTML = renderCountdownMarkup(0, 0, 0);
                    return;
                }
                var render = function () {
                    var now = Date.now();
                    var distance = Math.max(0, endTime - now);
                    var hours = Math.floor(distance / (1000 * 60 * 60));
                    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    var seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    valueEl.innerHTML = renderCountdownMarkup(hours, minutes, seconds);
                    if (distance <= 0) {
                        window.clearInterval(timer);
                        valueEl.innerHTML = renderCountdownMarkup(0, 0, 0);
                    }
                };
                render();
                var timer = window.setInterval(render, 1000);
            }
            function setupInfiniteProducts() {
                var grid = document.getElementById('home-infinite-products-grid');
                var status = document.getElementById('home-infinite-products-status');
                var sentinel = document.getElementById('home-infinite-products-sentinel');
                if (!grid || !status || !sentinel) {
                    return;
                }
                var setStatus = function (message, loading) {
                    if (!message) {
                        status.innerHTML = '';
                        return;
                    }
                    status.innerHTML = loading
                        ? '<span class="home-infinite-products-loader">' + message + '</span>'
                        : message;
                };
                var loadProducts = function () {
                    if (grid.getAttribute('data-loading') === 'true' || grid.getAttribute('data-complete') === 'true') {
                        return;
                    }
                    grid.setAttribute('data-loading', 'true');
                    setStatus('{{ translate('Loading products') }}', true);
                    var page = parseInt(grid.getAttribute('data-next-page') || '1', 10);
                    var limit = parseInt(grid.getAttribute('data-limit') || '30', 10);
                    var token = document.querySelector('meta[name="csrf-token"]');
                    var body = '_token=' + encodeURIComponent(token ? token.getAttribute('content') : '') +
                        '&page=' + encodeURIComponent(page) +
                        '&limit=' + encodeURIComponent(limit);
                    fetch('{{ route('home.section.infinite_products') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: body
                    })
                        .then(function (response) {
                            if (!response.ok) {
                                throw new Error('Request failed');
                            }
                            return response.text();
                        })
                        .then(function (html) {
                            var trimmed = html.trim();
                            if (!trimmed) {
                                if (!grid.children.length) {
                                    setStatus('{{ translate('Products will appear here once published items are available.') }}', false);
                                } else {
                                    setStatus('{{ translate('No more products to show.') }}', false);
                                }
                                grid.setAttribute('data-complete', 'true');
                                return;
                            }
                            var temp = document.createElement('div');
                            temp.innerHTML = trimmed;
                            var items = Array.prototype.slice.call(temp.children);
                            items.forEach(function (item) {
                                grid.appendChild(item);
                            });
                            grid.setAttribute('data-next-page', String(page + 1));
                            grid.setAttribute('data-loading', 'false');
                            if (items.length < limit) {
                                grid.setAttribute('data-complete', 'true');
                                setStatus('{{ translate('No more products to show.') }}', false);
                            } else {
                                setStatus('', false);
                            }
                            if (window.lazySizes && window.lazySizes.loader) {
                                window.lazySizes.loader.checkElems();
                            }
                        })
                        .catch(function () {
                            grid.setAttribute('data-loading', 'false');
                            setStatus('{{ translate('Could not load products. Please refresh and try again.') }}', false);
                        });
                };
                if ('IntersectionObserver' in window) {
                    var observer = new IntersectionObserver(function (entries) {
                        entries.forEach(function (entry) {
                            if (entry.isIntersecting) {
                                loadProducts();
                            }
                        });
                    }, {
                        rootMargin: '300px 0px'
                    });
                    observer.observe(sentinel);
                } else {
                    window.addEventListener('scroll', function () {
                        var rect = sentinel.getBoundingClientRect();
                        if (rect.top <= window.innerHeight + 300) {
                            loadProducts();
                        }
                    });
                }
                loadProducts();
            }
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-simple-countdown]').forEach(startCountdown);
                document.querySelectorAll('[data-scroll-target]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        var targetId = button.getAttribute('data-scroll-target');
                        var direction = button.getAttribute('data-scroll-direction');
                        var target = document.getElementById(targetId);
                        if (!target) {
                            return;
                        }
                        var firstItem = target.children[0];
                        var gap = parseFloat(window.getComputedStyle(target).columnGap || window.getComputedStyle(target).gap || '0') || 0;
                        var itemWidth = firstItem ? firstItem.getBoundingClientRect().width : 220;
                        var itemsPerStep = target.classList.contains('home-scroll-target-locked')
                            ? Math.max(1, Math.floor(target.clientWidth / Math.max(itemWidth + gap, 1)))
                            : 1;
                        var offset = target.classList.contains('home-scroll-target-locked')
                            ? (itemWidth + gap) * itemsPerStep
                            : Math.max(220, Math.floor(target.clientWidth * 0.7));
                        target.scrollBy({
                            left: direction === 'left' ? -offset : offset,
                            behavior: 'smooth'
                        });
                    });
                });
                setupInfiniteProducts();
            });
        })();
    </script>
@endsection
