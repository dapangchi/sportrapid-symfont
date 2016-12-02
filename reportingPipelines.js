// 0a) Matching topic ids
db.events.aggregate([
    {
        $match: {
            _id: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // event ids taken from active event + all descendant events
            }
        },
    },
    {
        $unwind: "$topics",
    },
    {
        $project: {
            "_id": 0,
            "topics": 1
        }
    }
])

db.topics.aggregate([
    {
        $match: {
            _id: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topic ids as fetched from above query
            }
        },
    },
    {
        $unwind: "$children",
    },
    {
        $project: {
            "_id": 0,
            "children": 1
        }
    }
])

// diff topics with children topics and repeat query for newly found topics to get the children of those children
// merge topic ids with children topic ids to get full list of topic ids


// 0b) Content stream
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            post_type: {
                $in: [1, 2, 4]
            }
        }
    }
])


// 1) Content Plot
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            },
            "valuation.lid": {
                $eq: ObjectId("56b2271e1fd96722d0b10789")
            }
        }
    },
    {
        $sort: {
            published_at: 1
        }
    },
    {
        $project: {
            "published_at": {
                $dateToString: {
                    format: "%Y-%m-%d %H:%M",
                    date: "$published_at"
                }
            },
            "url": 1,
            "images": {
                $slice: ["$images", 1]
            },
            "videos": {
                $slice: ["$videos", 1]
            },
            "valuation": {
                $filter: {
                    input: "$valuation",
                    as: "valuation",
                    cond: {
                        $eq: ["$$valuation.lid", ObjectId("56b2271e1fd96722d0b10789")]
                    }
                }
            }
        }
    },
    {
        $unwind: "$valuation"
    },
    {
        $unwind: {
            path: "$images",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: {
            path: "$videos",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $project: {
            "_id": 0,
            "published_at": 1,
            "url": 1,
            "value": "$valuation.value",
            "video_thumb": "$videos.thumbnail",
            "image_thumb": "$images.url",
        }
    },
])


// 2) Media Value
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [2, 4] // or [1,4] for images
            },
            "valuation.lid": {
                $eq: ObjectId("56b2271e1fd96722d0b10789")
            }
        }
    },
    {
        $project: {
            "published_at": 1,
            "valuation": {
                $filter: {
                    input: "$valuation",
                    as: "valuation",
                    cond: {
                        $eq: ["$$valuation.lid", ObjectId("56b2271e1fd96722d0b10789")]
                    }
                }
            }
        }
    },
    {
        $unwind: "$valuation"
    },
    {
        $group: {
            _id: {
                $dateToString: {
                    format: "%Y-%m-%d",
                    date: "$published_at"
                }
            },
            count: {
                $sum: 1
            },
            value: {
                $sum: "$valuation.value"
            }
        }
    },
    {
        $sort: {
            _id: 1
        }
    },
])


// 3) Media Exposure
db.dashboards_media_exposure.aggregate([
    {
        $match: {
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            date: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            }
        }
    },
    {
        $group: {
            _id: {
                $dateToString: {
                    format: "%Y-%m-%d",
                    date: "$date"
                }
            },
            num: { // query is run 2 times; num_images_all, num_videos_all
                $sum: '$count'
            },
        }
    },
    {
        $sort: {
            _id: 1
        }
    },
])

db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id --- NOTE: this is omitted for the "all" series
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            }
        }
    },
    {
        $project: {
            "published_at": 1
        }
    },
    {
        $group: {
            _id: {
                $dateToString: {
                    format: "%Y-%m-%d",
                    date: "$published_at"
                }
            },
            num: { // query is run 2 times; num_images_with_label, num_videos_with_label
                $sum: 1
            },
        }
    },
    {
        $sort: {
            _id: 1
        }
    },
])


// 4) Trending themes
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 2, 4]
            },
        }
    },
    {
        $project: {
            "_id": 0,
            "tags": 1 // or '$web_content.key_phrases' for digital
        }
    },
    {
        $unwind: "$tags"
    },
    {
        $group: {
            _id: "$tags",
            count: {
                $sum: 1
            }
        }
    },
    {
        $sort: {
            count: -1
        }
    },
    {
        $limit: 30
    }
]);


// 5) Sentiment
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 2, 4]
            },
            sentiment: { // or 'web_content.sentiment' for digital
                $exists: true
            }
        }
    },
    {
        $project: {
            "published_at": 1,
            "is_positive": {
                $cond: {
                    if: {
                        $and: [
                            {$lte: ["$sentiment", 1]},   // or '$web_content.sentiment' for digital
                            {$gt: ["$sentiment", .33]},  // or '$web_content.sentiment' for digital
                        ]
                    },
                    then: 1,
                    else: 0
                }
            },
            "is_neutral": {
                $cond: {
                    if: {
                        $and: [
                            {$lte: ["$sentiment", .33]},  // or '$web_content.sentiment' for digital
                            {$gte: ["$sentiment", -.33]}, // or '$web_content.sentiment' for digital
                        ]
                    },
                    then: 1,
                    else: 0
                }
            },
            "is_negative": {
                $cond: {
                    if: {
                        $and: [
                            {$lt: ["$sentiment", -0.33]}, // or '$web_content.sentiment' for digital
                            {$gte: ["$sentiment", -1]},   // or '$web_content.sentiment' for digital
                        ]
                    },
                    then: 1,
                    else: 0
                }
            },
        }
    },
    {
        $group: {
            _id: {
                $dateToString: {
                    format: "%Y-%m-%d",
                    date: "$published_at"
                }
            },
            num_positive: {
                $sum: '$is_positive'
            },
            num_neutral: {
                $sum: '$is_neutral'
            },
            num_negative: {
                $sum: '$is_negative'
            },
        }
    },
    {
        $sort: {
            _id: 1
        }
    },
])


// 6) Top sources
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 2, 4]
            },
            "valuation.lid": {
                $eq: ObjectId("56b2271e1fd96722d0b10789")
            }
        }
    },
    {
        $project: {
            "author_id": 1, // or "source": 1 for digital
            "valuation": {
                $filter: {
                    input: "$valuation",
                    as: "valuation",
                    cond: {
                        $eq: ["$$valuation.lid", ObjectId("56b2271e1fd96722d0b10789")]
                    }
                }
            }
        }
    },
    {
        $unwind: "$valuation"
    },
    {
        $group: {
            _id: "$author_id", // or $source for digital
            value: {
                $sum: "$valuation.value"
            }
        }
    },
    { // only for social:
        $lookup: {
            from: 'authors',
            localField: '_id',
            foreignField: '_id',
            as: 'author'
        }
    },
    { // only for social:
        $unwind: "$author"
    },
    {
        $project: {
            "_id": 0,
            "value": 1,

            // social
            "name": "$author.name",
            "screen_name": "$author.screen_name", // screen name is used as a fallback if name is empty
            "platform": "$author.platform",

            // digital
            "name": "$_id"
        }
    },
    {
        $sort: {
            value: -1
        }
    },
])


// 7) Top Locations
// Data not available for this at the moment


// 8a) Impressions
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            }
        },
    },
    {
        $lookup: {
            from: 'authors',
            localField: 'author_id',
            foreignField: '_id',
            as: 'author'
        }
    },
    {
        $unwind: "$author"
    },
    {
        $project: {
            "published_at": 1,
            "url": 1,
            "medias": 1,
            "videos": {
                $slice: ["$videos", 1]
            },
            "images": {
                $slice: ["$images", 1]
            },
            "impressions": "$author.statistics.followers"
        }
    },
    {
        $unwind: {
            path: "$images",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: {
            path: "$videos",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $sort: {
            'published_at': 1
        }
    },
    {
        $unwind: "$medias"
    },
    {
        $group: {
            _id: '$medias',
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$images.url",
            },
            video_thumb: {
                $first: "$videos.thumbnail",
            },
            impressions: {
                $sum: "$impressions"
            }
        }
    },
    {
        $sort: {
            impressions: -1
        }
    },
    {
        $limit: 50
    },

    /// ---- get the media location
    {
        $lookup: {
            from: 'medias',
            localField: '_id',
            foreignField: '_id',
            as: 'media'
        }
    },
    {
        $unwind: "$media"
    },
    {
        $unwind: "$media.locations"
    },
    {
        $lookup: {
            from: 'media_locations',
            localField: 'media.locations.loc_id',
            foreignField: '_id',
            as: 'media_location'
        }
    },
    {
        $unwind: "$media_location"
    },
    {
        $match: {
            'media.visual_labels.lid': {
                $eq: ObjectId("56b2271e1fd96722d0b1078b")
            },
            'media_location.alive': {
                $eq: true
            },
        }
    },
    {
        $group: {
            _id: '$_id',
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$image_thumb",
            },
            video_thumb: {
                $first: "$video_thumb",
            },
            media_location: {
                $first: '$media_location.url'
            },
            impressions: {
                $first: "$impressions"
            }
        }
    },
    {
        $sort: {
            impressions: -1
        }
    },
    {
        $limit: 20
    },
    {
        $project: {
            _id: 0,
            url: 1,
            image_thumb: 1,
            video_thumb: 1,
            media_location: 1,
            impressions: 1
        }
    }
])


// 8b) Engagement
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            }
        }
    },
    {
        $project: {
            "published_at": 1,
            "url": 1,
            "medias": 1,
            "videos": {
                $slice: ["$videos", 1]
            },
            "images": {
                $slice: ["$images", 1]
            },
            "engagement": {
                $sum: ["$statistics.likes", "$statistics.comments", "$statistics.shares"],
            }
        }
    },
    {
        $unwind: {
            path: "$images",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: {
            path: "$videos",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $sort: {
            'published_at': 1
        }
    },
    {
        $unwind: "$medias"
    },
    {
        $group: {
            _id: '$medias',
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$images.url",
            },
            video_thumb: {
                $first: "$videos.thumbnail",
            },
            engagement: {
                $sum: "$engagement"
            }
        }
    },
    {
        $sort: {
            engagement: -1
        }
    },
    {
        $limit: 50
    },

    /// ---- get the media location
    {
        $lookup: {
            from: 'medias',
            localField: '_id',
            foreignField: '_id',
            as: 'media'
        }
    },
    {
        $unwind: "$media"
    },
    {
        $unwind: "$media.locations"
    },
    {
        $lookup: {
            from: 'media_locations',
            localField: 'media.locations.loc_id',
            foreignField: '_id',
            as: 'media_location'
        }
    },
    {
        $unwind: "$media_location"
    },
    {
        $match: {
            'media.visual_labels.lid': {
                $eq: ObjectId("56b2271e1fd96722d0b1078b")
            },
            'media_location.alive': {
                $eq: true
            },
        }
    },
    {
        $group: {
            _id: '$_id',
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$image_thumb",
            },
            video_thumb: {
                $first: "$video_thumb",
            },
            media_location: {
                $first: '$media_location.url'
            },
            engagement: {
                $first: "$engagement"
            }
        }
    },
    {
        $sort: {
            engagement: -1
        }
    },
    {
        $limit: 20
    },
    {
        $project: {
            _id: 0,
            url: 1,
            image_thumb: 1,
            video_thumb: 1,
            media_location: 1,
            engagement: 1
        }
    }
])


// 9) Sources: Most viewed videos
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56b2271e1fd96722d0b10789") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [2, 4]
            },
            "statistics.views": { // or statistics.reach for digital
                $gt: 0
            }
        },
    },
    {
        $project: {
            "published_at": 1,
            "url": 1,
            "medias": 1,
            "videos": {
                $slice: ["$videos", 1]
            },
            "images": {
                $slice: ["$images", 1]
            },
            "views": "$statistics.views"  // or $statistics.reach for digital
        }
    },
    {
        $unwind: {
            path: "$images",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: {
            path: "$videos",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $sort: {
            'published_at': 1
        }
    },
    {
        $unwind: "$medias"
    },
    {
        $group: {
            _id: '$medias',
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$images.url",
            },
            video_thumb: {
                $first: "$videos.thumbnail",
            },
            views: {
                $sum: "$views"
            }
        }
    },
    {
        $sort: {
            views: -1
        }
    },
    {
        $limit: 10
    },
    {
        $project: {
            _id: 0,
            url: 1,
            image_thumb: 1,
            video_thumb: 1,
            views: 1
        }
    }
])


// 10) Most powerful a) images b) videos
db.posts.aggregate([
    {
        $match: {
            verified: {
                $eq: ObjectId("56be0875ba648b1560188c79") // label id
            },
            topics: {
                $in: [ObjectId("569925ff82a4c5588675a831")] // topics from (0a)
            },
            published_at: {
                $gte: new Date('2015-12-06'),
                $lte: new Date('2016-10-27')
            },
            platform: {
                $eq: ObjectId("56bb2a9fd4c6cfe4b41a0564") // or $in: [all other platform ids] for social
            },
            post_type: {
                $in: [1, 4] // or [2, 4] for videos
            },
            platform: { // if platform is selected, otherwise this is left out (replaces above platform match if present)
                $eq: ObjectId("56814ffe36f34e3539a4b356")
            }
        },
    },
    { // only for social:
        $lookup: {
            from: 'authors',
            localField: 'author_id',
            foreignField: '_id',
            as: 'author'
        }
    },
    { // only for social:
        $unwind: "$author"
    },
    {
        $project: {
            "published_at": 1,
            "url": 1,
            "platform": 1,
            "medias": 1,
            "videos": {
                $slice: ["$videos", 1]
            },
            "images": {
                $slice: ["$images", 1]
            },
            "valuation": {
                $filter: {
                    input: "$valuation",
                    as: "valuation",
                    cond: {
                        $eq: ["$$valuation.lid", ObjectId("56be0875ba648b1560188c79")]
                    }
                }
            },
            "impressions": "$author.statistics.followers", // or $statistics.reach for digital
            "engagement": {
                $sum: ["$statistics.likes", "$statistics.comments", "$statistics.shares"],
            },
            "views": "$statistics.views" // only used for videos, and not available for twitter and instagram
        }
    },
    {
        $unwind: {
            path: "$images",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: {
            path: "$videos",
            preserveNullAndEmptyArrays: true
        }
    },
    {
        $unwind: "$valuation"
    },
    {
        $sort: {
            'valuation.value': -1
        }
    },
    {
        $limit: 100
    },
    {
        $match: {
            'valuation.value': {
                $gt: 0
            }
        }
    },
    {
        $unwind: "$medias"
    },
    {
        $lookup: {
            from: 'medias',
            localField: 'medias',
            foreignField: '_id',
            as: 'media'
        }
    },
    {
        $match: {
            'media.visual_labels.lid': {
                $eq: ObjectId("56be0875ba648b1560188c79")
            }
        }
    },
    {
        $group: {
            _id: {
                _id: '$medias',
                platform: '$platform'
            },
            url: {
                $first: "$url"
            },
            image_thumb: {
                $first: "$images.url",
            },
            video_thumb: {
                $first: "$videos.thumbnail",
            },
            value: {
                $sum: "$valuation.value"
            },
            impressions: {
                $sum: "$impressions"
            },
            engagement: {
                $sum: "$engagement"
            },
            views: {
                $sum: "$views"
            }
        }
    },
    {
        $sort: {
            value: -1
        }
    },
    {
        $group: {
            _id: '$_id._id',
            url: {
                $first: "$url"
            },
            platforms: {
                $push: {
                    id: "$_id.platform",
                    url: "$url"
                }
            },
            image_thumb: {
                $first: "$image_thumb",
            },
            video_thumb: {
                $first: "$video_thumb",
            },
            value: {
                $sum: "$value"
            },
            impressions: {
                $sum: "$impressions"
            },
            engagement: {
                $sum: "$engagement"
            },
            views: {
                $sum: "$views"
            }
        }
    },
    {
        $sort: {
            value: -1
        }
    },
    {
        $limit: 5
    },

    /// ---- get the media location
    {
        $lookup: {
            from: 'medias',
            localField: '_id',
            foreignField: '_id',
            as: 'media'
        }
    },
    {
        $unwind: "$media"
    },
    {
        $unwind: "$media.locations"
    },
    {
        $lookup: {
            from: 'media_locations',
            localField: 'media.locations.loc_id',
            foreignField: '_id',
            as: 'media_location'
        }
    },
    {
        $match: {
            'media_location.alive': {
                $eq: true
            }
        }
    },
    {
        $group: {
            _id: '$_id',
            url: {
                $first: "$url"
            },
            platforms: {
                $first: "$platforms"
            },
            image_thumb: {
                $first: "$image_thumb"
            },
            video_thumb: {
                $first: "$video_thumb"
            },
            media_location: {
                $first: '$media_location.url'
            },
            value: {
                $first: "$value"
            },
            impressions: {
                $first: "$impressions"
            },
            engagement: {
                $first: "$engagement"
            },
            views: {
                $first: "$views"
            }
        }
    },
    {
        $sort: {
            value: -1
        }
    },
    {
        $limit: 5
    },
    {
        $unwind: '$media_location'
    }
])
